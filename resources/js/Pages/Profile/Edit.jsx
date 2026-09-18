import { Head } from "@inertiajs/react";

import AppLayout from "@/Layouts/AppLayout";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm";

export default function Edit() {
    return (
        <AppLayout
            breadcrumbs={[
                { label: "Dashboard", href: route("dashboard") },
                { label: "My Account" },
            ]}
        >
            <Head title="My Account" />

            <div className="mx-auto max-w-2xl space-y-6">
                <div className="rounded-lg bg-white p-5 shadow-sm sm:p-6">
                    <UpdateProfileInformationForm />
                </div>
                <div className="rounded-lg bg-white p-5 shadow-sm sm:p-6">
                    <UpdatePasswordForm />
                </div>
            </div>
        </AppLayout>
    );
}
