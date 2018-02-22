-- Run `php artisan migrate --pretend` to generate the queries for this file

\c lumen

create table "migrations" ("id" integer primary key not null, "migration" varchar(255) not null, "batch" integer not null);

create table "cache" ("key" varchar(255) not null, "value" text not null, "expiration" integer not null);
alter table "cache" add constraint "cache_key_unique" unique ("key");
insert into "migrations" (id, migration, batch) values (1, '0000_00_00_000001_create_cache_table', 1);

create table "jobs" ("id" bigserial primary key not null, "queue" varchar(255) not null, "payload" text not null, "attempts" smallint not null, "reserved_at" integer null, "available_at" integer not null, "created_at" integer not null);
create index "jobs_queue_index" on "jobs" ("queue");
insert into "migrations" (id, migration, batch) values (2, '0000_00_00_000002_create_jobs_table', 1);

create table "failed_jobs" ("id" bigserial primary key not null, "connection" text not null, "queue" text not null, "payload" text not null, "exception" text not null, "failed_at" timestamp(0) without time zone default CURRENT_TIMESTAMP(0) not null);
insert into "migrations" (id, migration, batch) values (3, '0000_00_00_000003_create_failed_jobs_table', 1);

create table "accounts" ("number" varchar(255) primary key not null, "owner" varchar(255) not null, "balance" decimal(12, 2) not null default 0, "closed" boolean not null default false);
insert into "migrations" (id, migration, batch) values (4, '0000_00_00_000004_create_accounts_table', 2);
