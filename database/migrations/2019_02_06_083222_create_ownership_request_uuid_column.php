<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOwnershipRequestUuidColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ownership_requests', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->unique('user_id'); // A user can only submit one ownership request per business.
        });
    }

}
