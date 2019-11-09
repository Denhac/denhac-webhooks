Hello! <br>
<br>

{{ $updateMessage }}
<br><br>
<h3>Activated cards</h3>
@forelse($activatedCards as $card)
    <b>{{ $card["first_name"] . " " . $card["last_name"] }}</b>
    - {{ $card["card"] }}<br>
@empty
    There were no new activated cards<br>
@endforelse

<br>
<h3>Deactivated cards</h3>
@forelse($deactivatedCards as $card)
    <b>{{ $card["first_name"] . " " . $card["last_name"] }}</b>
    - {{ $card["card"] }}<br>
@empty
    There were no new deactivated cards<br>
@endforelse
