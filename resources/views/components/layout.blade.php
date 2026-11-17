@props(['title' => ''])
<x-layouts.public :title="$title">{{ $slot }}</x-layouts.public>