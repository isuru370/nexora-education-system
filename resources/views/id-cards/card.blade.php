{{--
    One ID card from the Card Designer layout (App\Support\CardDesign::card()).
    Plain absolute boxes in mm so the same markup works in the browser,
    html2canvas (PNG download) and dompdf (PDF).

    $card   CardDesign::card(...)
    $class  extra CSS class (optional)
    $attrs  extra attributes (optional, already safe)
--}}
<div class="nx-id-card {{ $class ?? '' }}" {!! $attrs ?? '' !!}
    style="position:relative;overflow:hidden;box-sizing:content-box;padding:0;margin:0;width:{{ $card['width'] }}mm;height:{{ $card['height'] }}mm;background:#ffffff;font-family:'DejaVu Sans', Arial, Helvetica, sans-serif;">
    @if ($card['background'])
        <img src="{{ $card['background'] }}" alt="" class="nx-id-card-bg"
            style="position:absolute;left:0;top:0;width:{{ $card['width'] }}mm;height:{{ $card['height'] }}mm;display:block;object-fit:cover;">
    @endif
    @foreach ($card['fields'] as $field)
        <div class="nx-id-field nx-id-field-{{ $field['key'] }}" data-field="{{ $field['key'] }}" style="{{ $field['style'] }}">{!! $field['html'] !!}</div>
    @endforeach
</div>
