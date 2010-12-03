
import os.path

from twisted.internet import error
from twisted.internet import reactor
from twisted.internet.defer import Deferred
from twisted.internet.protocol import ProcessProtocol
from twisted.python import log
from twisted.python.failure import Failure

import ssscrape


class Worker(ProcessProtocol):
    '''
    Protocol class for worker executables.

    This class communicates with child processes, keeps track of their state
    and saves the information in the C{Job} instance.
    '''

    def __init__(self, job):
        '''
        Initialize a new C{WorkerProcessProtocol} instance.
        '''

        assert isinstance(job, ssscrape.Job)

        # Initialization
        self._kill_calls = []
        self.job = job

    def __str__(self):
        return 'Worker for %s' % self.job

    def run(self):
        '''
        Run the worker process.

        @return:
        A Deffered that fires when the process is done (or if an error
        occurred).
        '''
        self.on_finished = Deferred()

        # The program name may be an absolute path or a relative path.
        if self.job.program.startswith('/'):
            full_path = self.job.program
        else:
            full_path = os.path.join(
                ssscrape.config.get_string('manager', 'worker-directory'),
                self.job.program)

        # FIXME: check for executable bit as well
        if os.path.exists(full_path):
            # Execute the program
            cmd_line = '%s %s' % (full_path, self.job.args)
            self.spawn_params = ('/bin/sh', '-c', cmd_line)
            log.msg('Executing %s: %s' % (self.job, cmd_line))

            # Since this class is a ProcessProtocol subclass, we can pass 'self' to
            # the spawnProcess call.
            process_environ = {
                'SSSCRAPE_JOB_ID': str(self.job.id),
            }
            if self.job.task_id is not None:
                process_environ['SSSCRAPE_TASK_ID'] = str(self.job.task_id)

            try:
                process = reactor.spawnProcess(self, self.spawn_params[0],
                    self.spawn_params, process_environ)
                # The running process is now the transport for this ProcessProtocol
                assert process is self.transport
            except TypeError: # when called with non-ascii characters (See #161)
                self.job.set_message('Worker script %s was called with illegal arguments' % full_path)
                self.job.mark_as_finished(ssscrape.Job.STATES.PERMANENT_ERROR)
                self._finish()
        else:
            self.job.set_message('Worker script %s does not exist' % full_path)
            self.job.mark_as_finished(ssscrape.Job.STATES.PERMANENT_ERROR)
            self._finish()

        # Return the Deferred instance
        return self.on_finished


    def _finish(self):
        '''
        Do some finalization and clean up.
        '''
        self._cancel_kill_calls()
        result = (self, self.job)
        self.on_finished.callback(result)


    def _cancel_kill_calls(self):
        '''
        Cancel pending kill calls for this process.
        '''
        for c in self._kill_calls:
            try:
                c.cancel()
            except (error.AlreadyCancelled, error.AlreadyCalled):
                pass


    def connectionMade(self):
        '''
        Called when the process starts executing.
        '''

        # Store the process id
        self.job.set_pid(self.transport.pid)

        # Setup a buffer where all the output is stored
        self.output_buffer = []

        # We don't use stdin, so immediately close it
        self.transport.closeStdin()

        # kill after a while
        c = reactor.callLater(ssscrape.config.worker_get_time(self.job.type, 'kill-after'), self.abort)
        self._kill_calls.append(c)


    def outReceived(self, data):
        '''
        Callback when data is received on stdout.
        '''

        self.output_buffer.append(data)


    def errReceived(self, data):
        '''
        Callback when data is received on stderr.
        '''

        self.output_buffer.append(data)


    def processEnded(self, exit_status):
        '''
        Callback when the process has ended.
        '''

        # The exit code is only available if the process exited normally. If it
        # was killed by a signal, the exitCode value is None. The signal number
        # will be saved as a negative integer to make it easy to see which
        # processes exited normally and which ones were killed.
        exit_code = exit_status.value.exitCode

        # Handle the exit code
        if exit_code is not None:
            # Processes exited normally. A few exit codes have special meaning
            # and are treated as such.
            assert exit_code >= 0
            exit_code_to_job_state_mapping = {
                0: ssscrape.Job.STATES.COMPLETED,
                1: ssscrape.Job.STATES.TEMPORARY_ERROR,
                2: ssscrape.Job.STATES.PERMANENT_ERROR,
            }
            try:
                new_state = exit_code_to_job_state_mapping[exit_code]
            except KeyError:
                err_msg = \
                    '%s returned invalid exit code (%d). WORKER SHOULD BE FIXED: %s' \
                    % (self.job, exit_code, self.job.program)
                log.msg(err_msg)

                self.job.set_message('Invalid exit code: %d' % exit_code)
                new_state = ssscrape.Job.STATES.TEMPORARY_ERROR

        else:
            # The process was killed by a signal.
            assert isinstance(exit_status.value, error.ProcessTerminated)

            import signal

            signal_no = exit_status.value.signal
            signal_name = {
                    signal.SIGHUP:  'SIGHUP',
                    signal.SIGSTOP: 'SIGSTOP',
                    signal.SIGINT:  'SIGINT',
                    signal.SIGKILL: 'SIGKILL',
                    signal.SIGTERM: 'SIGTERM',
                    signal.SIGSEGV: 'SIGSEGV',
                }.get(signal_no, str(signal_no)) # name, or signal number otherwise

            err_msg = '%s killed by signal %s' % (self.job, signal_name)
            log.msg(err_msg)
            self.job.set_message('Killed by signal %s' % signal_name)
            new_state = ssscrape.Job.STATES.PERMANENT_ERROR

        # Get the output into a single string
        #output = unicode(u''.join(self.output_buffer))
        unicode_lines = [] 
        for line in self.output_buffer:
            try:
                unicode_line = unicode(line)
            except UnicodeDecodeError:
                try:
                    unicode_line = line.decode('utf-8')
                except UnicodeDecodeError:
                    unicode_line = line.decode('latin1') # latin1 never fails :)
            unicode_lines.append(unicode_line)
        output = u''.join(unicode_lines)

        # TODO: reschedule failed jobs that had temporary errors or that were
        # killed by a signal. See ticket:20

        # Mark the job as finished

        if exit_code is None:
            exit_code_to_store = 0 - signal_no
        else:
            exit_code_to_store = exit_code

        if ssscrape.config.worker_get_bool(self.job.type, 'auto-reschedule-after-temporary-error', False) \
                and (new_state == ssscrape.Job.STATES.TEMPORARY_ERROR) \
                and (self.job.attempts >= (ssscrape.config.worker_get_int(self.job.type, 'auto-reschedule-max-attempts', 1) + 1)):
            new_state = ssscrape.Job.STATES.PERMANENT_ERROR
            log.msg('%s has reached maximum number of attempts, resulting in a permanent error.' % (self.job))

        self.job.mark_as_finished(new_state, exit_code_to_store, output)

        self._finish()


    def _send_signal(self, signal):
        '''
        Send a signal to the process, if running.

        @param signal: The signal to send
        '''
        try:
            self.transport.signalProcess(signal)
        except error.ProcessExitedAlready:
            pass

    def abort(self):
        '''
        Abort the process.
        '''

        log.msg('Sending SIGHUP to %s.' % self.job)
        self._send_signal('HUP')

        # The worker process can now try to shutdown cleanly, if it catches SIGHUP. 
        # Schedule a call to really kill it after a while
        c = reactor.callLater(10, self.force_abort)
        self._kill_calls.append(c)


    def force_abort(self):
        '''
        Forcibly abort the process.
        '''

        log.msg('Sending SIGTERM to %s.' % self.job)
        self._send_signal('TERM')
