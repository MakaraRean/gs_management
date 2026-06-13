<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Authenticate using a fresh copy of the user.
     *
     * Production reloads the user from the session on every request, so an
     * acting user always reflects the current database state (e.g. a
     * `business_id` set after the model was first instantiated). Mirror that
     * here so tests aren't tripped up by stale in-memory attributes.
     */
    public function actingAs(Authenticatable $user, $guard = null): static
    {
        if ($user instanceof Model && $user->exists) {
            $user = $user->fresh() ?? $user;
        }

        return parent::actingAs($user, $guard);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
