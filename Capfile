require 'capistrano/version'
load 'deploy'

# You need to fill in the 2 vars below
set :application, "shuffler"
set :domain, "shuffler.fm"
set :repository,  "git@github.com:shuffler/ssscrape.git"

server "#{application}", :app, :web, :db, :primary => true

set :scm, :git
set :branch, "master"
set :deploy_to, "/home/shuffler/apps/ssscrape"
set :use_sudo, false
set :keep_releases, 2
#set :git_shallow_clone, 1

# keep remote copy of check out things
set :deploy_via, :remote_cache

# some SSH things

# this forwards the agent which is handy because we have no public repo
ssh_options[:forward_agent] = true

default_run_options[:pty] = true
set :use_sudo, false

namespace :deploy do

  desc <<-DESC
  A macro-task that updates the code and fixes the symlink.
  DESC
  task :default do
    transaction do
      update_code
      symlink
    end
  end
  
  task :update_code, :except => { :no_release => true } do
    on_rollback { run "rm -rf #{release_path}; true" }
    strategy.deploy!
  end

  task :after_deploy do
    cleanup
  end

  task :after_symlink do
    ;
  end
    
end