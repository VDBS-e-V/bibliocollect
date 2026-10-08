@props(['url'])
<tr>
<td class="header" align="center">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img src="{{ asset('brand/vdbs/mail-logo.png') }}" width="180" height="76" alt="VDBS e. V." style="display: block; margin: 0 auto 10px; border: 0; height: auto;">
<span style="display: block; color: #58275a; font-size: 24px; font-weight: bold; line-height: 1.2;">{!! $slot !!}</span>
<span style="display: block; margin: 8px auto 0; width: 48px; height: 3px; background-color: #2dc08e; line-height: 3px; font-size: 0;">&nbsp;</span>
</a>
</td>
</tr>
