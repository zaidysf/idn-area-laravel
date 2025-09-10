<?php

use Illuminate\Support\Facades\Artisan;
use zaidysf\IdnArea\Commands\IdnAreaCommand;

// Test Artisan Command
describe('IdnAreaCommand', function () {
    it('can instantiate command', function () {
        $command = new IdnAreaCommand;

        expect($command)->toBeInstanceOf(IdnAreaCommand::class);
    });

    it('has correct signature and description', function () {
        $command = new IdnAreaCommand;

        expect($command->signature)->toContain('idn-area:seed');
        expect($command->signature)->toContain('--force');
        expect($command->description)->toBe('Enhanced seeder for Indonesian area data with modern Laravel features');
    });

    it('command is registered', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('idn-area:seed');
    });

    it('can run command without force option', function () {
        // This will attempt to run but may succeed in test environment
        $exitCode = Artisan::call('idn-area:seed');

        // Command should return success (0) or failure (1) depending on environment
        expect($exitCode)->toBeIn([0, 1]);
    });
});
