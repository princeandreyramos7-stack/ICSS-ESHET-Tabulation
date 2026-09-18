import { Head, Link, usePage } from "@inertiajs/react";
import { ArrowLeft, Home } from "lucide-react";

import BrandBackdrop from "@/Components/Brand/BrandBackdrop";
import ConferenceLogo from "@/Components/Brand/ConferenceLogo";
import DeveloperCredit from "@/Components/Brand/DeveloperCredit";
import Eyebrow from "@/Components/Brand/Eyebrow";

const MESSAGES = {
    401: ["Sign in required", "Please sign in to continue."],
    403: ["Access denied", "Your account is not allowed to open this page."],
    404: ["Page not found", "The page you are looking for does not exist or was moved."],
    419: ["Session expired", "Your session timed out. Please sign in again."],
    429: ["Too many requests", "Please wait a moment and try again."],
    500: ["Something went wrong", "An unexpected error occurred. Please try again; if it persists, contact the secretariat."],
    503: ["Under maintenance", "The system is briefly unavailable. Please check back shortly."],
};

export default function Error({ status }) {
    const { auth } = usePage().props;
    const [title, text] = MESSAGES[status] ?? ["Error", "An unexpected error occurred."];
    const homeHref = auth?.user ? route("dashboard") : route("welcome");

    return (
        <BrandBackdrop className="flex items-center justify-center p-6">
            <Head title={title} />
            <div className="w-full max-w-md text-center animate-in fade-in zoom-in-95 duration-500">
                <ConferenceLogo className="mx-auto size-16" />
                <Eyebrow className="mt-6">Error {status}</Eyebrow>
                <h1 className="mt-2 text-3xl font-extrabold tracking-tight">{title}</h1>
                <p className="mt-3 text-sm text-emerald-100/80">{text}</p>
                <div className="mt-8 flex flex-wrap justify-center gap-3">
                    <button
                        type="button"
                        onClick={() => window.history.back()}
                        className="inline-flex items-center gap-2 rounded-full border border-white/40 px-5 py-2.5 text-sm font-semibold transition hover:bg-white/10"
                    >
                        <ArrowLeft className="size-4" /> Go back
                    </button>
                    <Link
                        href={homeHref}
                        className="inline-flex items-center gap-2 rounded-full bg-amber-400 px-5 py-2.5 text-sm font-bold text-emerald-950 transition hover:bg-amber-300"
                    >
                        <Home className="size-4" /> {auth?.user ? "Dashboard" : "Home"}
                    </Link>
                </div>
                <div className="mt-12 flex justify-center">
                    <DeveloperCredit tone="dark" compact />
                </div>
            </div>
        </BrandBackdrop>
    );
}
