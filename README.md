# Fsm
A simple finite state machine

# Install
`composer require sidfate/fsm`

# Usages
```
$fsm = new Fsm([
    'init'=> 'green',
    'events'=> [
        ['name'=> 'warn', 'from'=> 'green', 'to'=> 'yellow'],
        ['name'=> 'stop', 'from'=> 'yellow', 'to'=> 'red'],
        ['name'=> 'go', 'from'=> 'red', 'to'=> 'green'],
    ]
]);

$fsm->onWarn(function () {
   echo 'I am warn';
});

echo $fsm->now();	// green
$fsm->warn();		// I am warn
echo $fsm->now();	// yellow

```