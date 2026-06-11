<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_defaults_to_khmer_locale_when_no_cookie_is_set(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('locale', 'km')
            ->has('translations')
        );
    }

    #[Test]
    public function it_uses_locale_from_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withUnencryptedCookie('locale', 'en')
            ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
        );
    }

    #[Test]
    public function it_falls_back_to_khmer_for_unsupported_locale_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withUnencryptedCookie('locale', 'fr')
            ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('locale', 'km')
        );
    }

    #[Test]
    public function it_sets_locale_cookie_via_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/locale', ['locale' => 'en']);

        $response->assertRedirect();
        $response->assertPlainCookie('locale', 'en');
    }

    #[Test]
    public function it_rejects_unsupported_locale_via_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/locale', ['locale' => 'fr']);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_includes_translations_in_shared_props(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('locale', 'km')
            ->where('translations', fn ($translations) => ($translations['dashboard.title'] ?? null) === 'ផ្ទាំងគ្រប់គ្រង')
        );
    }

    #[Test]
    public function it_includes_english_translations_when_locale_is_en(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withUnencryptedCookie('locale', 'en')
            ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
            ->where('translations', fn ($translations) => ($translations['dashboard.title'] ?? null) === 'Dashboard')
        );
    }
}
