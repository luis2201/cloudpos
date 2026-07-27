@props(['label', 'value', 'detail', 'icon', 'tone' => 'blue'])

<article class="stat-card">
    <div class="stat-card__top">
        <span class="stat-card__label">{{ $label }}</span>
        <span class="stat-card__icon stat-card__icon--{{ $tone }}"><x-icon :name="$icon" /></span>
    </div>
    <strong class="stat-card__value">{{ $value }}</strong>
    <span class="stat-card__detail">{{ $detail }}</span>
</article>
