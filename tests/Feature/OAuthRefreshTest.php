<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ExercisesOAuthFlow;
use Tests\Support\IssuesApiKeys;
use Tests\TestCase;

final class OAuthRefreshTest extends TestCase
{
    use ExercisesOAuthFlow;
    use IssuesApiKeys;
    use RefreshDatabase;

    public function test_solicitud_renovacion_expiracion_revocacion_y_reuso(): void
    {
        $this->seedLabRbac();
        $this->exerciseOAuthFlow();
    }

    public function test_rotar_un_access_vigente_tambien_lo_invalida(): void
    {
        $this->seedLabRbac();
        $pair = $this->postJson('/api/auth/oauth/token', $this->oauthFixture())->assertOk()->json();
        $this->getJson('/api/tickets', $this->oauthHeadersFor($pair['access_token']))->assertOk();
        $this->postJson('/api/auth/oauth/refresh', ['refresh_token' => $pair['refresh_token']])->assertOk();
        $this->getJson('/api/tickets', $this->oauthHeadersFor($pair['access_token']))->assertUnauthorized();
    }
}
