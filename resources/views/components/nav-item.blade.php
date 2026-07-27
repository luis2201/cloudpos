@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}" @class(['nav-item', 'is-active' => $active]) {{ $attributes }}>
    <span class="nav-item__icon"><x-icon :name="$icon" /></span>
    <span>{{ $slot }}</span>
</a>
