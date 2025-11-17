<?php
declare(strict_types=1);

namespace App\Shared\Services;

use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Support\Collection;

/**
 * LookupService builds dropdown datasets reused across controllers.
 */
class LookupService
{
    /**
     * Get active developers (users) ordered alphabetically.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function activeDevelopers(): Collection
    {
        return User::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
    }

    /**
     * Get lightweight site dropdown options.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function siteOptions(): Collection
    {
        return Site::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Site $site): array => [
                'id' => $site->id,
                'name' => $site->name,
            ]);
    }

    /**
     * Get client dropdown options that include company metadata.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function clientOptions(): Collection
    {
        return Client::orderBy('name')
            ->get(['id', 'name', 'company'])
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'company' => $client->company,
            ]);
    }
}

