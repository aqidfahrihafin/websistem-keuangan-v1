<div>
    <form wire:submit="submit" class="space-y-5">
        <x-form-field label="Email / NIS / No. KK" :error="$errors->first('login')" hint="Staf & wali pakai email, santri pakai NIS, atau wali baru bisa pakai No. KK.">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm0 0 8 7 8-7"/></svg>
                </span>
                <input type="text" wire:model="login" class="field-input pl-10.5" autofocus autocomplete="username" placeholder="admin@pesantren.test">
            </div>
        </x-form-field>

        <x-form-field label="Kata Sandi" x-data="{ show: false }">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 11V7a6 6 0 1 1 12 0v4M5 11h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1Z"/></svg>
                </span>
                <input :type="show ? 'text' : 'password'" wire:model="password" class="field-input pl-10.5 pr-10.5" autocomplete="current-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                <button type="button" @click="show = ! show" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 transition hover:text-slate-600" tabindex="-1">
                    <svg x-show="! show" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg x-show="show" x-cloak class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.6 10.6a3 3 0 0 0 4.24 4.24M9.36 5.36A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a13.5 13.5 0 0 1-3.17 4.24M6.6 6.6C4 8.3 2 12 2 12s3.5 7 10 7c1.3 0 2.47-.27 3.5-.71"/></svg>
                </button>
            </div>
        </x-form-field>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" wire:model="remember" class="field-checkbox">
                Ingat saya
            </label>
        </div>

        <button type="submit" class="btn-primary w-full py-2.5" wire:loading.attr="disabled" wire:target="submit">
            <svg wire:loading wire:target="submit" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.37 0 0 5.37 0 12h4Z"/></svg>
            <span wire:loading.remove wire:target="submit">Masuk</span>
            <span wire:loading wire:target="submit">Memproses&hellip;</span>
        </button>
    </form>
</div>
