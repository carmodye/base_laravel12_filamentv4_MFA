<p>Widget loaded</p>
<div class="p-4 bg-white rounded shadow">
    @if ($status)
        <div class="text-green-600">
            {{ $status }}
        </div>
    @endif

    @if ($errors)
        <ul class="text-red-600">
            @foreach ($errors as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form wire:submit.prevent="upload" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div class="space-y-1">
            <label for="file" class="text-sm font-medium">Image or video</label>
            <input type="file" wire:model="file" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Upload</button>
    </form>
</div>
