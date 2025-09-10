<?php

declare(strict_types=1);

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('models should extend Eloquent')
    ->expect('zaidysf\IdnArea\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('commands should extend Command')
    ->expect('zaidysf\IdnArea\Commands')
    ->toExtend('Illuminate\Console\Command');

arch('facades should extend Facade')
    ->expect('zaidysf\IdnArea\Facades')
    ->toExtend('Illuminate\Support\Facades\Facade');

arch('service provider should extend PackageServiceProvider')
    ->expect('zaidysf\IdnArea\IdnAreaServiceProvider')
    ->toExtend('Spatie\LaravelPackageTools\PackageServiceProvider');

arch('models should use factories')
    ->expect('zaidysf\IdnArea\Models')
    ->toUse('Illuminate\Database\Eloquent\Factories\HasFactory');

arch('it does not use env outside of config')
    ->expect('env')
    ->not->toBeUsed()
    ->ignoring([
        'config',
        'tests',
    ]);

arch('controllers should not have die or exit')
    ->expect(['die', 'exit'])
    ->not->toBeUsed()
    ->ignoring(['tests']);

arch('it will not use compact() function')
    ->expect('compact')
    ->not->toBeUsed()
    ->ignoring(['tests']);

arch('ensure no use of globals')
    ->expect(['$GLOBALS', '$_GET', '$_POST', '$_SESSION', '$_COOKIE', '$_FILES', '$_ENV'])
    ->not->toBeUsed();

arch('classes should be final or abstract')
    ->expect('zaidysf\IdnArea')
    ->classes()
    ->toBeFinal()
    ->ignoring([
        'zaidysf\IdnArea\Models',
        'zaidysf\IdnArea\IdnAreaServiceProvider',
        'zaidysf\IdnArea\Commands',
        'zaidysf\IdnArea\Facades',
        'zaidysf\IdnArea\Services',
        'zaidysf\IdnArea\Database\Factories',
    ]);
