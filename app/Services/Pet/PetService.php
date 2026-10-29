<?php

namespace App\Services\Pet;



use App\Services\BaseService;

use App\Models\Pet;
use App\Models\User;
use KaziOmar\SmartUpload\Facades\Upload;
use Illuminate\Http\Request;

class PetService extends BaseService
{


    protected string $modelClass = Pet::class;

    protected function getAllowedFilters(): array
    {
        return ['type', 'breed', 'gender', 'user_id'];
    }

    protected function getAllowedIncludes(): array
    {
        return ['user'];
    }

    protected function getAllowedSorts(): array
    {
        return ['created_at', 'name', 'age_years', 'weight'];
    }

    /**
     * Create a new pet for a user.
     */
    public function createPet(array $data, User $user, Request $request): Pet
    {
        $data['user_id'] = $user->id;

        if ($request->hasFile('photo')) {
            $data['photo'] = Upload::file($request->file('photo'))
                ->scaleDown(800, 800)
                ->toWebp(80)
                ->save('pets')
                ->path;
        }

        return $this->create($data);
    }

    /**
     * Update a pet.
     */
    public function updatePet(Pet $pet, array $data, Request $request): Pet
    {
        if ($request->hasFile('photo')) {
            $data['photo'] = Upload::file($request->file('photo'))
                ->scaleDown(800, 800)
                ->toWebp(80)
                ->replace($pet->photo)
                ->save('pets')
                ->path;
        }

        return $this->update($pet->id, $data);
    }

    /**
     * Delete a pet and its photo (handled automatically by HasUploadPurge).
     */
    public function deletePet(Pet $pet): bool
    {
        return $this->delete($pet->id);
    }
}



