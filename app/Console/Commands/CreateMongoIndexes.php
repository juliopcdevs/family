<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Family;
use App\Models\ShoppingItem;
use App\Models\ShoppingListItem;
use App\Models\CalendarEvent;
use App\Models\Task;
use App\Models\Birthday;
use App\Models\TemporarySearchLog;

class CreateMongoIndexes extends Command
{
    protected $signature = 'mongo:indexes';
    protected $description = 'Create MongoDB indexes for all collections';

    public function handle()
    {
        $this->info('Creating indexes...');

        User::raw()->createIndex(['email' => 1], ['unique' => true]);
        $this->info('users indexes created');

        Family::raw()->createIndex(['code' => 1], ['unique' => true]);
        $this->info('families indexes created');

        ShoppingItem::raw()->createIndex(['slug' => 1], ['unique' => true]);
        ShoppingItem::raw()->createIndex(['name' => 'text']);
        $this->info('shopping_items indexes created');

        ShoppingListItem::raw()->createIndex(['family_id' => 1, 'is_in_cart' => 1]);
        ShoppingListItem::raw()->createIndex(['family_id' => 1, 'usage_count' => -1, 'last_used_at' => -1]);
        ShoppingListItem::raw()->createIndex(['family_id' => 1, 'item_slug' => 1]);
        $this->info('shopping_list_items indexes created');

        CalendarEvent::raw()->createIndex(['family_id' => 1, 'date' => 1]);
        $this->info('calendar_events indexes created');

        Task::raw()->createIndex(['family_id' => 1, 'is_completed' => 1, 'created_at' => -1]);
        $this->info('tasks indexes created');

        Birthday::raw()->createIndex(['family_id' => 1]);
        $this->info('birthdays indexes created');

        TemporarySearchLog::raw()->createIndex(['created_at' => 1], ['expireAfterSeconds' => 7776000]);
        TemporarySearchLog::raw()->createIndex(['search_term' => 1]);
        $this->info('temporary_search_logs indexes created');

        $this->info('All indexes created successfully!');
    }
}
