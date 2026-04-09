<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class PlatformLoginController
{
    /**
     * @var Guard|\Illuminate\Auth\SessionGuard
     */
    private Guard $guard;

    public function __construct(AuthFactory $auth)
    {
        $this->guard = $auth->guard(config('platform.guard'));
    }

    /**
     * @return View
     */
    public function showLoginForm(Request $request)
    {
        $userId = $request->cookie($this->nameForLock());

        /** @var EloquentUserProvider $provider */
        $provider = $this->guard->getProvider();

        $model = $provider->createModel()->find($userId);

        $isLockUser = $model !== null
            && ($model->exists ?? false)
            && ! empty($model->document);

        return view('platform::auth.login', [
            'isLockUser' => $isLockUser,
            'lockUser'   => $model,
        ]);
    }

    /**
     * @throws ValidationException
     *
     * @return JsonResponse|RedirectResponse
     */
    public function login(Request $request, CookieJar $cookieJar)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $auth = $this->guard->attempt(
            [
                'document' => (string) $request->input('username'),
                'password' => (string) $request->input('password'),
            ],
            $request->boolean('remember')
        );

        if (! $auth) {
            throw ValidationException::withMessages([
                'username' => __('The details you entered did not match our records. Please double-check and try again.'),
            ]);
        }

        if ($request->boolean('remember')) {
            $cookieJar->queue(
                $cookieJar->forever($this->nameForLock(), $this->guard->id())
            );
        }

        return $this->sendLoginResponse($request);
    }

    /**
     * @return RedirectResponse|JsonResponse
     */
    private function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect()->intended(route(config('platform.index')));
    }

    /**
     * @return RedirectResponse
     */
    public function resetCookieLockMe(CookieJar $cookieJar)
    {
        $cookieJar->queue($cookieJar->forget($this->nameForLock()));

        return redirect()->route('platform.login');
    }

    private function nameForLock(): string
    {
        return sprintf('%s_%s', $this->guard->getName(), '_orchid_lock');
    }
}
