<?php

namespace App\Console\Commands;

use App\Models\Author;
use Illuminate\Console\Command;

class CreateAuthorCommand extends Command
{
    protected $signature = 'author:create';
    protected $description = 'Create a new author';

    public function handle(): int
    {
        $firstName = $this->ask('What is the author\'s first name?');
        $lastName = $this->ask('What is the author\'s last name?');

        $author = Author::create([
            'name' => "$firstName $lastName",
        ]);

        $this->info("Author '{$author->name}' created successfully (ID: {$author->id}).");

        return Command::SUCCESS;
    }
}
