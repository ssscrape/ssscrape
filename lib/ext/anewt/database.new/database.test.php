<?php

/* test named placeholders with associative arrays */
// $pq = $connection->prepare('INSERT INTO test_table VALUES ( ?bool:bool_var?, ?int:int_var?, ?float:float_var?, ?string:string_var?, ?date:date_var?, ?datetime:datetime_var?, ?timestamp:timestamp_var?, ?time:time_var?, ?raw:raw_var?)');
// 
// $values = array(
// 	'bool_var' => null,
// 	'int_var' => null,
// 	'float_var' => null,
// 	'string_var' => null,
// 	'date_var' => null,
// 	'datetime_var' => null,
// 	'timestamp_var' => null,
// 	'time_var' => null,
// 	'raw_var' => null
// );
// 
// /* Test null */
// $pq->execute($values);
// 
// #/* Test booleans */
// $values['bool_var'] = true;
// $pq->execute($values);
// $values['bool_var'] = false;
// $pq->execute($values);
// 
// $values['bool_var'] = null;
// 
// #/* Test ints */
// $values['int_var'] = 2;
// $pq->execute($values);
// $values['int_var'] = '3';
// $pq->execute($values);
// $values['int_var'] = null;
// 
// /* Test floats */
// $values['float_var'] = 2.0;
// $pq->execute($values);
// $values['float_var'] = 1.234;
// $pq->execute($values);
// $values['float_var'] = 3;
// $pq->execute($values);
// $values['float_var'] = null;
// 
// 
// /* Test strings */
// //class StringWrap { function render() {return 'test';} }
// $values['string_var'] = 'Test';
// $pq->execute($values);
// $values['string_var'] = 'Te\';st';
// $pq->execute($values);
// $values['string_var'] = "\t\n;--'";
// $pq->execute($values);
// $values['string_var'] = 2;
// $pq->execute($values);
// $values['string_var'] = new StringWrap();
// $pq->execute($values);
// $values['string_var'] = null;
// 
// /* Test dates */
// $values['date_var'] = '2006-06-06';
// $pq->execute($values);
// $values['date_var'] = AnewtDateTime::now();
// $pq->execute($values);
// $values['date_var'] = null;
// 
// /* Test datetimes */
// $values['datetime_var'] = '2006-06-06 06:06:06';
// $pq->execute($values);
// $values['datetime_var'] = AnewtDateTime::now();
// $pq->execute($values);
// $values['datetime_var'] = AnewtDateTime::sql(AnewtDateTime::now());
// $pq->execute($values);
// $values['datetime_var'] = null;
// 
// /* Test timestamps */
// $values['timestamp_var'] = '2006-06-06 06:06:06';
// $pq->execute($values);
// $values['timestamp_var'] = AnewtDateTime::now();
// $pq->execute($values);
// $values['timestamp_var'] = AnewtDateTime::sql(AnewtDateTime::now());
// $pq->execute($values);
// $values['timestamp_var'] = null;
// 
// /* Test times */
// $values['time_var'] = '06:06:06';
// $pq->execute($values);
// $values['time_var'] = AnewtDateTime::now();
// $pq->execute($values);
// $values['time_var'] = null;
// 
// /* Test raw */
// $values['raw_var'] = '"?int?"';
// $pq->execute($values);
// $values['raw_var'] = null;
// 
// /* Test all at once */
// $values = array(
// 	'bool_var' => true,
// 	'int_var' => 3,
// 	'float_var' => 2.0,
// 	'string_var' => 'Test',
// 	'date_var' => '2006-06-06',
// 	'datetime_var' => '2006-06-06 06:06:06',
// 	'timestamp_var' => '2006-06-06 06:06:06',
// 	'time_var' => '06:06:06',
// 	'raw_var' => '"?raw?"'
// );
// $pq->execute($values);
// 
// /* test named placeholders with containers */
// $values = new Container(array(
// 	'bool_var' => null,
// 	'int_var' => null,
// 	'float_var' => null,
// 	'string_var' => null,
// 	'date_var' => null,
// 	'datetime_var' => null,
// 	'timestamp_var' => null,
// 	'time_var' => null,
// 	'raw_var' => null
// ));
// 
// /* Test null */
// $pq->execute($values);
// 
// #/* Test booleans */
// $values->set('bool_var', true);
// $pq->execute($values);
// $values->set('bool_var', false);
// $pq->execute($values);
// 
// $values->set('bool_var', null);
// 
// #/* Test ints */
// $values->set('int_var', 2);
// $pq->execute($values);
// $values->set('int_var', '3');
// $pq->execute($values);
// $values->set('int_var', null);
// 
// /* Test floats */
// $values->set('float_var', 2.0);
// $pq->execute($values);
// $values->set('float_var', 1.234);
// $pq->execute($values);
// $values->set('float_var', 3);
// $pq->execute($values);
// $values->set('float_var', null);
// 
// 
// /* Test strings */
// //class StringWrap { function render() {return 'test';} }
// $values->set('string_var', 'Test');
// $pq->execute($values);
// $values->set('string_var', 'Te\';st');
// $pq->execute($values);
// $values->set('string_var', "\t\n;--'");
// $pq->execute($values);
// $values->set('string_var', 2);
// $pq->execute($values);
// $values->set('string_var', new StringWrap());
// $pq->execute($values);
// $values->set('string_var', null);
// 
// /* Test dates */
// $values->set('date_var', '2006-06-06');
// $pq->execute($values);
// $values->set('date_var', AnewtDateTime::now());
// $pq->execute($values);
// $values->set('date_var', null);
// 
// /* Test datetimes */
// $values->set('datetime_var', '2006-06-06 06:06:06');
// $pq->execute($values);
// $values->set('datetime_var', AnewtDateTime::now());
// $pq->execute($values);
// $values->set('datetime_var', AnewtDateTime::sql(AnewtDateTime::now()));
// $pq->execute($values);
// $values->set('datetime_var', null);
// 
// /* Test timestamps */
// $values->set('timestamp_var', '2006-06-06 06:06:06');
// $pq->execute($values);
// $values->set('timestamp_var', AnewtDateTime::now());
// $pq->execute($values);
// $values->set('timestamp_var', AnewtDateTime::sql(AnewtDateTime::now()));
// $pq->execute($values);
// $values->set('timestamp_var', null);
// 
// /* Test times */
// $values->set('time_var', '06:06:06');
// $pq->execute($values);
// $values->set('time_var', AnewtDateTime::now());
// $pq->execute($values);
// $values->set('time_var', null);
// 
// /* Test raw */
// $values->set('raw_var', '"?int?"');
// $pq->execute($values);
// $values->set('raw_var', null);
// 
// /* Test all at once */
// $values->seed(array(
// 	'bool_var' => true,
// 	'int_var' => 3,
// 	'float_var' => 2.0,
// 	'string_var' => 'Test',
// 	'date_var' => '2006-06-06',
// 	'datetime_var' => '2006-06-06 06:06:06',
// 	'timestamp_var' => '2006-06-06 06:06:06',
// 	'time_var' => '06:06:06',
// 	'raw_var' => '"?raw?"'
// ));
// $pq->execute($values);

?>
