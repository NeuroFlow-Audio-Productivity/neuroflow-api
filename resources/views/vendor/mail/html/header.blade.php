@props(['url'])
@php
    $brandName = config('mail.from.name') ?: config('app.name', 'Neuroflow');
    $initial = mb_strtoupper(mb_substr($brandName, 0, 1));
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" class="brand-link">
<span class="brand-mark">{{ $initial }}</span>
<span class="brand-name">{{ $brandName }}</span>
</a>
</td>
</tr>
