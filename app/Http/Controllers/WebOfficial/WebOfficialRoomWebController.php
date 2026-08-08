<?php

namespace App\Http\Controllers\WebOfficial;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebOfficial\StoreWebOfficialRoomWebRequest;
use App\Http\Requests\WebOfficial\UpdateWebOfficialRoomWebRequest;
use App\Models\WebOfficialRoom;
use App\Services\WebOfficial\WebOfficialRoomPersister;
use App\Services\WebOfficialMediaStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebOfficialRoomWebController extends Controller
{
    public function index(Request $request): Response
    {
        $query = WebOfficialRoom::query()->orderBy('sort_order');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        $rooms = $query->paginate(20)->withQueryString();

        return Inertia::render('web-official/rooms/index', [
            'rooms' => $rooms->through(fn (WebOfficialRoom $room): array => [
                'id' => $room->id,
                'slug' => $room->slug,
                'name' => $room->name,
                'price' => $room->price,
                'badge' => $room->badge,
                'is_active' => $room->is_active,
                'sort_order' => $room->sort_order,
            ]),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'is_active' => $request->input('is_active'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('web-official/rooms/create');
    }

    public function store(
        StoreWebOfficialRoomWebRequest $request,
        WebOfficialRoomPersister $persister,
        WebOfficialMediaStorageService $storage,
    ): RedirectResponse {
        $room = new WebOfficialRoom;
        $validated = $this->validatedRoomPayload($request->validated());

        $persister->fill($room, $validated, isCreate: true);

        if ($request->hasFile('photo_file')) {
            $persister->applyPhotoUpload($room, $request->file('photo_file'), $storage);
        }

        $room->save();

        return redirect()
            ->route('web-official.rooms.index')
            ->with('success', 'Kamar berhasil ditambahkan.');
    }

    public function edit(WebOfficialRoom $room): Response
    {
        return Inertia::render('web-official/rooms/edit', [
            'room' => [
                'id' => $room->id,
                'slug' => $room->slug,
                'name' => $room->name,
                'tagline' => $room->tagline,
                'badge' => $room->badge,
                'description' => $room->description,
                'price' => $room->price,
                'photo' => $room->photo_url,
                'facilities_text' => implode("\n", $room->facilities ?? []),
                'sort_order' => $room->sort_order,
                'is_active' => $room->is_active,
            ],
        ]);
    }

    public function update(
        UpdateWebOfficialRoomWebRequest $request,
        WebOfficialRoom $room,
        WebOfficialRoomPersister $persister,
        WebOfficialMediaStorageService $storage,
    ): RedirectResponse {
        $validated = $this->validatedRoomPayload($request->validated());
        $persister->fill($room, $validated, isCreate: false);

        if ($request->hasFile('photo_file')) {
            $persister->applyPhotoUpload($room, $request->file('photo_file'), $storage);
        }

        $room->save();

        return redirect()
            ->route('web-official.rooms.index')
            ->with('success', 'Kamar berhasil diperbarui.');
    }

    public function destroy(WebOfficialRoom $room): RedirectResponse
    {
        $room->is_active = false;
        $room->save();

        return redirect()
            ->route('web-official.rooms.index')
            ->with('success', 'Kamar berhasil dinonaktifkan.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function validatedRoomPayload(array $validated): array
    {
        $validated['facilities'] = WebOfficialRoomPersister::parseFacilitiesInput(
            isset($validated['facilities_text']) ? (string) $validated['facilities_text'] : null
        );
        unset($validated['facilities_text']);

        return $validated;
    }
}
