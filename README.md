# Fsm
A simple finite state machine

# Install
`composer require sidfate/fsm`

# Usage
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

# Document
[Click me](https://github.com/Sidfate/fsm/wiki)

# License
MIT
