<?php

namespace App\Console\Commands;

use App\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create
                            {email? : The administrator email address}
                            {--name=Administrator : The administrator display name}
                            {--role=super_admin : Administrator role}
                            {--password= : The password (omit this option to enter it securely)}';

    protected $description = 'Create a local administrator account';

    public function handle(): int
    {
        $email = (string) ($this->argument('email') ?: $this->ask('Email address'));
        $name = (string) $this->option('name');
        $role = (string) $this->option('role');
        $password = (string) ($this->option('password') ?: $this->secret('Password'));

        $validator = Validator::make(compact('name', 'email', 'password', 'role'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:12'],
            'role' => ['required', Rule::enum(AdminRole::class)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        Admin::query()->create(compact('name', 'email', 'password', 'role'));

        $this->info("Administrator {$email} created.");

        return self::SUCCESS;
    }
}
