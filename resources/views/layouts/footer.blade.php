<footer class="text-muted text-center text-small gradient">
    <p class="mb-1">&copy; 2022 Легке замовлення таксі </p>
    <ul class="list-inline">
        <li class="list-inline-item"><a href="{{ route('taxi-gdbr') }}" target="_blank">Конфіденційність</a></li>
        <li class="list-inline-item"><a href="{{route('homeStreet', [$phone = '000', $user_name = 'Новий замовник'])}}" target="_blank">Розрахунок вартості</a></li>
        <li class="list-inline-item"><a href="{{ route('taxi-umovy') }}" target="_blank">Умови</a></li>
        <li class="list-inline-item"><a href="{{ route('feedback') }}" target="_blank">Підтримка</a></li>
        <li class="list-inline-item"><a href="https://www.facebook.com/people/Taxi-Easy-Ua/100085343706349/" target="_blank">Ми на Фейсбук</a></li>
    </ul>
</footer>
