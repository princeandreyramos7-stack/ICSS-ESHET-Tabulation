import { useEffect } from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { toast } from "sonner";

import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { PasswordInput } from "@/Components/ui/password-input";
import { Spinner } from "@/Components/ui/spinner";
import ConferenceLogo from "@/Components/Brand/ConferenceLogo";
import GuestLayout from "@/Layouts/GuestLayout";

export default function Login({ status }) {
    const { conference } = usePage().props;
    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false,
    });

    useEffect(() => {
        if (status) toast.success(status, { id: "auth-status" });
    }, [status]);

    const submit = (e) => {
        e.preventDefault();
        post(route("login"), {
            onError: (errs) => {
                const first = Object.values(errs)[0];
                if (first) toast.error(first, { id: "auth-error" });
            },
            onFinish: () => reset("password"),
        });
    };

    return (
        <GuestLayout>
            <Head title="Log in" />

            <div className="mb-6">
                <div className="mb-4 md:hidden">
                    <ConferenceLogo className="size-14" />
                </div>
                <p className="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-600">
                    Panel of Evaluators
                </p>
                <h1 className="mt-1 text-2xl font-extrabold tracking-tight text-emerald-950">
                    Welcome back
                </h1>
                <p className="mt-1 text-sm text-gray-500">
                    Sign in to the {conference?.short_name} to rate presentations.
                </p>
            </div>

            <form onSubmit={submit} className="flex flex-col gap-5">
                <div className="grid gap-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        placeholder="you@example.com"
                        required
                        autoFocus
                        value={data.email}
                        autoComplete="username"
                        onChange={(e) => setData("email", e.target.value)}
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password">Password</Label>
                    <PasswordInput
                        id="password"
                        required
                        placeholder="Enter your password"
                        value={data.password}
                        autoComplete="current-password"
                        onChange={(e) => setData("password", e.target.value)}
                    />
                    <InputError message={errors.password} />
                </div>

                <label className="flex items-center gap-2 text-sm text-gray-600">
                    <input
                        type="checkbox"
                        name="remember"
                        checked={data.remember}
                        onChange={(e) => setData("remember", e.target.checked)}
                        className="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600"
                    />
                    Keep me signed in on this device
                </label>

                <Button
                    type="submit"
                    disabled={processing}
                    className="h-11 w-full bg-amber-400 text-base font-bold text-emerald-950 hover:bg-amber-300"
                >
                    {processing ? (
                        <>
                            <Spinner /> Signing in...
                        </>
                    ) : (
                        "Sign in"
                    )}
                </Button>
            </form>

            <p className="mt-6 text-center text-xs text-gray-400">
                Accounts are issued by the conference secretariat. Forgot your password? Ask the
                administrator to reset it.
            </p>
        </GuestLayout>
    );
}
