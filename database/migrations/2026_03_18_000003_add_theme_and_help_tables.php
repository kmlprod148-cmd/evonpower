<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add theme to users only if missing.
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'theme')) {
                    $table->enum('theme', ['light', 'dark', 'system'])
                        ->default('system')
                        ->after('timezone');
                }
            });
        }

        if (!Schema::hasTable('help_categories')) {
            Schema::create('help_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('help_categories')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('help_articles')) {
            Schema::create('help_articles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('help_categories')->cascadeOnDelete();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('content');
                $table->text('excerpt')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->integer('view_count')->default(0);
                $table->boolean('is_published')->default(false);
                $table->boolean('is_featured')->default(false);
                $table->integer('order')->default(0);
                $table->json('tags')->nullable();
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('help_searches')) {
            Schema::create('help_searches', function (Blueprint $table) {
                $table->id();
                $table->string('query');
                $table->integer('results_count')->default(0);
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_searches');
        Schema::dropIfExists('help_articles');
        Schema::dropIfExists('help_categories');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'theme')) {
                    $table->dropColumn('theme');
                }
            });
        }
    }
};
