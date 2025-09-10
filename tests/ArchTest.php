<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->each->not->toBeUsed();

arch('models extend Illuminate\\Database\\Eloquent\\Model')
    ->expect('zaidysf\\IdnArea\\Models')
    ->toExtend('Illuminate\\Database\\Eloquent\\Model');

arch('models are in Models namespace')
    ->expect('zaidysf\\IdnArea\\Models')
    ->toBeClasses();

arch('services are in Services namespace')
    ->expect('zaidysf\\IdnArea\\Services')
    ->toBeClasses();

arch('commands extend Illuminate\\Console\\Command')
    ->expect('zaidysf\\IdnArea\\Commands')
    ->toExtend('Illuminate\\Console\\Command');

arch('facades extend Illuminate\\Support\\Facades\\Facade')
    ->expect('zaidysf\\IdnArea\\Facades')
    ->toExtend('Illuminate\\Support\\Facades\\Facade');

arch('ensure no Laravel helpers are used in models')
    ->expect('zaidysf\\IdnArea\\Models')
    ->not->toUse(['request', 'session', 'auth', 'config']);
