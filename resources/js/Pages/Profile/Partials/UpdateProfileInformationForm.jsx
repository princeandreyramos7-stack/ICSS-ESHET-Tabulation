import { useForm, usePage } from "@inertiajs/react";

import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Spinner } from "@/Components/ui/spinner";

export default function UpdateProfileInformationForm() {
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing } = useForm({
        name: user.name,
        email: user.email,
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route("profile.update"), { preserveScroll: true });
    };

    return (
        <section>
            <header>
                <h2 className="text-lg font-semibold text-gray-900">Profile</h2>
                <p className="mt-1 text-sm text-gray-600">
                    Your name appears on result sheets and the signature block.
                </p>
            </header>

            <form onSubmit={submit} className="mt-5 grid gap-4">
                <div className="grid gap-1.5">
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData("name", e.target.value)}
                        required
                        autoComplete="name"
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData("email", e.target.value)}
                        required
                        autoComplete="username"
                    />
                    <InputError message={errors.email} />
                </div>

                <div>
                    <Button type="submit" disabled={processing} className="bg-emerald-700 hover:bg-emerald-800">
                        {processing ? (
                            <>
                                <Spinner /> Saving...
                            </>
                        ) : (
                            "Save"
                        )}
                    </Button>
                </div>
            </form>
        </section>
    );
}
