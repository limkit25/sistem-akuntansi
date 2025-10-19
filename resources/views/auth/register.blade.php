<x-guest-layout>
    <div class="card-body register-card-body">
        <p class="login-box-msg">Register a new membership</p>

        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form action="{{ route('register') }}" method="POST">
            @csrf
            
            <div class="input-group mb-3">
                <input type="text" name="name" class="form-control" placeholder="Full name" value="{{ old('name') }}" required autofocus>
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-user"></span>
                    </div>
                </div>
            </div>
            
            <div class="input-group mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email" value="{{ old('email') }}" required>
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-envelope"></span>
                    </div>
                </div>
            </div>
            
            <div class="input-group mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-lock"></span>
                    </div>
                </div>
            </div>
            
            <div class="input-group mb-3">
                <input type="password" name="password_confirmation" class="form-control" placeholder="Retype password" required>
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-lock"></span>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-8">
                    <a href="{{ route('login') }}" class="text-center">I already have a membership</a>
                </div>
                <div class="col-4">
                    <button type="submit" class="btn btn-primary btn-block">Register</button>
                </div>
                </div>
        </form>
    </div>
    </x-guest-layout>