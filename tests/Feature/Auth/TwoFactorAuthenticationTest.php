<?php

use App\Models\User;

test('disabling two factor authentication requires password confirmation', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user)
        ->delete(route('two-factor.disable'))
        ->assertRedirect(route('password.confirm'));

    expect($user->fresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

test('regenerating recovery codes requires password confirmation', function () {
    $user = User::factory()->withTwoFactor()->create();
    $recoveryCodes = $user->two_factor_recovery_codes;

    $this->actingAs($user)
        ->post(route('two-factor.regenerate-recovery-codes'))
        ->assertRedirect(route('password.confirm'));

    expect($user->fresh()->two_factor_recovery_codes)->toBe($recoveryCodes);
});
