<?php

use App\Enums\RoleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->date('date_of_birth')->nullable()->after('last_name');
            $table->string('phone_number')->nullable()->after('date_of_birth');
            $table->text('address')->nullable()->after('phone_number');
            $table->string('profile_picture')->nullable()->after('address');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('profile_picture');
            $table->string('role')->default(RoleType::Guest)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'date_of_birth',
                'phone_number',
                'address',
                'profile_picture',
                'status',
                'role'
            ]);
        });
    }
};
