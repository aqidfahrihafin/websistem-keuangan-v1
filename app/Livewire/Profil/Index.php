<?php

namespace App\Livewire\Profil;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Index extends Component
{
    public string $name = '';

    public ?string $email = null;

    public ?string $phone = null;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
    }

    public function simpanProfil(): void
    {
        $user = Auth::user();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update($data);

        session()->flash('status', 'Profil berhasil diperbarui.');
    }

    public function simpanPassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Auth::user()->update(['password' => Hash::make($this->password), 'must_change_password' => false]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('status', 'Kata sandi berhasil diubah.');
    }

    public function render()
    {
        return view('livewire.profil.index', [
            'title' => 'Profil Saya',
            'user' => Auth::user(),
        ]);
    }
}
