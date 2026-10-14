<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema base consolidado del laboratorio (v1.0).
 *
 * Un solo archivo con las 17 tablas en orden de FKs. La historia de cómo se
 * llegó aquí está en git log; a partir de este punto, cualquier cambio de
 * esquema va en una migración NUEVA, nunca editando esta.
 *
 * Nota: no existe tabla oauth_auth_codes (grant ROPC, sin Auth Code).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 32)->unique();
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 32)->unique();
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('OPEN');
            $table->string('priority', 20)->default('MEDIUM');
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'priority']);
            $table->index('created_by');
            $table->index('assigned_to');
            $table->index('assigned_role_id');
        });

        Schema::create('ticket_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('viewed_at');
            $table->index(['ticket_id', 'viewed_at']);
            $table->index(['user_id', 'viewed_at']);
        });

        Schema::create('ticket_escalations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('from_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('to_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('escalated_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('escalated_at');
            $table->text('note')->nullable();
            $table->index(['ticket_id', 'escalated_at']);
        });

        Schema::create('ticket_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);
            $table->text('from_value')->nullable();
            $table->text('to_value')->nullable();
            $table->timestamp('created_at');
            $table->index(['ticket_id', 'created_at']);
        });

        Schema::create('api_keys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('key_hash', 255)->unique();
            $table->string('label', 100)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
        });

        Schema::create('jwt_revocations', function (Blueprint $table): void {
            $table->id();
            $table->string('jti', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at');
            $table->string('replaced_by', 64)->nullable();
        });

        Schema::create('oauth_clients', function (Blueprint $table): void {
            $table->id();
            $table->string('client_id', 64)->unique();
            $table->string('client_secret', 255);
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::create('oauth_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('access_token', 255)->unique();
            $table->foreignId('client_id')->constrained('oauth_clients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('scopes');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
        });

        Schema::create('oauth_refresh_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('refresh_token', 255)->unique();
            $table->foreignId('access_token_id')->constrained('oauth_access_tokens')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('replaced_by', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('experiments', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('mechanism', 20);
            $table->string('status', 20)->default('PENDING');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('experiment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
            $table->string('event_type', 64);
            $table->jsonb('payload');
            $table->timestamp('created_at');
            $table->index(['experiment_id', 'created_at']);
        });

        Schema::create('metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
            $table->string('mechanism', 20);
            $table->string('operation', 64);
            $table->smallInteger('http_status');
            $table->integer('latency_ms');
            $table->integer('auth_ms')->default(0);
            $table->integer('credential_size');
            $table->smallInteger('round_trips');
            $table->string('failed_layer', 20)->nullable();
            $table->timestamp('occurred_at');
            $table->index(['experiment_id', 'mechanism', 'operation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
        Schema::dropIfExists('experiment_events');
        Schema::dropIfExists('experiments');
        Schema::dropIfExists('oauth_refresh_tokens');
        Schema::dropIfExists('oauth_access_tokens');
        Schema::dropIfExists('oauth_clients');
        Schema::dropIfExists('jwt_revocations');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('ticket_history');
        Schema::dropIfExists('ticket_escalations');
        Schema::dropIfExists('ticket_views');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
    }
};
