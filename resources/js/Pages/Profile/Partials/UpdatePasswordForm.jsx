import { useRef } from "react";
import { useForm } from "@inertiajs/react";

import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import { PasswordInput } from "@/Components/ui/password-input";
import { Spinner } from "@/Components/ui/spinner";

export default function UpdatePasswordForm() {
    const passwordInput = useRef();
    const currentPasswordInput = useRef();

    const { data, setData, errors, put, reset, processing } = useForm({
        current_password: "",
        password: "",
        password_confirmation: "",
    });

    const submit = (e) => {
        e.preventDefault();
        put(route("password.update"), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errs) => {
                if (errs.password) {
                    reset("password", "password_confirmation");
                    passwordInput.current?.focus();
                }
                if (errs.current_password) {
                    reset("current_password");
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <section>
            <header>
                <h2 className="text-lg font-semibold text-gray-900">Change password</h2>
                <p className="mt-1 text-sm text-gray-600">Use at least 8 characters.</p>
            </header>

            <form onSubmit={submit} className="mt-5 grid gap-4">
                <div className="grid gap-1.5">
                    <Label htmlFor="current_password">Current password</Label>
                    <PasswordInput
                        id="current_password"
                        ref={currentPasswordInput}
                                                value={data.current_password}
                        onChange={(e) => setData("current_password", e.target.value)}
                        autoComplete="current-password"
                    />
                    <InputError message={errors.current_password} />
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="password">New password</Label>
                    <PasswordInput
                        id="password"
                        ref={passwordInput}
                                                value={data.password}
                        onChange={(e) => setData("password", e.target.value)}
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="password_confirmation">Confirm new password</Label>
                    <PasswordInput
                        id="password_confirmation"
                                                value={data.password_confirmation}
                        onChange={(e) => setData("password_confirmation", e.target.value)}
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <div>
                    <Button type="submit" disabled={processing} className="bg-emerald-700 hover:bg-emerald-800">
                        {processing ? (
                            <>
                                <Spinner /> Updating...
                            </>
                        ) : (
                            "Update password"
                        )}
                    </Button>
                </div>
            </form>
        </section>
    );
}
