@props(['options' => [10, 25, 50, 100]])

<select {{ $attributes->class(['field-input w-auto']) }}>
    @foreach ($options as $n)
        <option value="{{ $n }}">{{ $n }} / halaman</option>
    @endforeach
</select>
