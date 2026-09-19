import { useEffect, useMemo, useState } from "react";
import { Head, router, useForm } from "@inertiajs/react";
import { Pencil, Plus, Search, Trash2, UserPlus } from "lucide-react";

import ConfirmDialog from "@/Components/ConfirmDialog";
import Pagination from "@/Components/Pagination";
import InputError from "@/Components/InputError";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Select } from "@/Components/ui/select";
import { Spinner } from "@/Components/ui/spinner";
import AppLayout from "@/Layouts/AppLayout";
import { usePagination } from "@/hooks/use-pagination";

function EvaluatorFormDialog({ open, onOpenChange, evaluator, tracks }) {
    const isEdit = Boolean(evaluator?.id);
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        name: "",
        email: "",
        password: "",
        track_id: "",
    });

    useEffect(() => {
        if (!open) return;
        clearErrors();
        setData({
            name: evaluator?.name ?? "",
            email: evaluator?.email ?? "",
            password: "",
            track_id: evaluator?.track_id ? String(evaluator.track_id) : "",
        });
    }, [open, evaluator]);

    const submit = (e) => {
        e.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        };
        if (isEdit) {
            put(route("admin.evaluators.update", evaluator.id), options);
        } else {
            post(route("admin.evaluators.store"), options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={(o) => !processing && onOpenChange(o)}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={submit} autoComplete="off">
                    <DialogHeader>
                        <DialogTitle>{isEdit ? "Edit Evaluator" : "Add Evaluator"}</DialogTitle>
                        <DialogDescription>
                            {isEdit
                                ? "Leave the password blank to keep the current one."
                                : "Share the email and password with the evaluator so they can sign in."}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="mt-4 grid gap-4">
                        <div className="grid gap-1.5">
                            <Label htmlFor="name">Full name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData("name", e.target.value)}
                                maxLength={255}
                                required
                                autoFocus
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="track_id">Track (panel assignment)</Label>
                            <Select
                                id="track_id"
                                value={data.track_id}
                                onChange={(e) => setData("track_id", e.target.value)}
                                required
                            >
                                <option value="" disabled>
                                    Select the track this evaluator will score
                                </option>
                                {tracks.map((t) => (
                                    <option key={t.id} value={t.id}>
                                        Track {t.number}: {t.name}
                                    </option>
                                ))}
                            </Select>
                            <InputError message={errors.track_id} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="email">Email (used to sign in)</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData("email", e.target.value)}
                                maxLength={255}
                                required
                                autoComplete="off"
                            />
                            <InputError message={errors.email} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="password">
                                {isEdit ? "New password (optional)" : "Password"}
                            </Label>
                            <Input
                                id="password"
                                type="text"
                                value={data.password}
                                onChange={(e) => setData("password", e.target.value)}
                                minLength={isEdit ? undefined : 8}
                                required={!isEdit}
                                autoComplete="new-password"
                                placeholder="At least 8 characters"
                            />
                            <InputError message={errors.password} />
                        </div>
                    </div>

                    <DialogFooter className="mt-6">
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={processing}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing} className="bg-emerald-700 hover:bg-emerald-800">
                            {processing ? (
                                <>
                                    <Spinner /> Saving...
                                </>
                            ) : isEdit ? (
                                "Save changes"
                            ) : (
                                "Add evaluator"
                            )}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Index({ evaluators, tracks = [] }) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [deleteProcessing, setDeleteProcessing] = useState(false);
    const [search, setSearch] = useState("");
    // "" = all, "none" = unassigned, otherwise a track id.
    const [trackFilter, setTrackFilter] = useState("");

    const visible = useMemo(() => {
        const q = search.trim().toLowerCase();
        return evaluators.filter((u) => {
            if (trackFilter === "none" && u.track_id) return false;
            if (trackFilter && trackFilter !== "none" && String(u.track_id) !== trackFilter) return false;
            if (!q) return true;
            return (
                u.name.toLowerCase().includes(q) ||
                u.email.toLowerCase().includes(q) ||
                (u.track_name ?? "").toLowerCase().includes(q)
            );
        });
    }, [evaluators, search, trackFilter]);

    const pager = usePagination(visible, 25);
    const unassignedCount = evaluators.filter((u) => !u.track_id).length;

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };
    const openEdit = (evaluator) => {
        setEditing(evaluator);
        setFormOpen(true);
    };

    const confirmDelete = () => {
        if (!deleting) return;
        setDeleteProcessing(true);
        router.delete(route("admin.evaluators.destroy", deleting.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleteProcessing(false);
                setDeleting(null);
            },
        });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { label: "Dashboard", href: route("admin.dashboard") },
                { label: "Evaluators" },
            ]}
            actions={
                <Button onClick={openCreate} className="bg-emerald-700 hover:bg-emerald-800">
                    <Plus />
                    <span className="hidden sm:inline">Add evaluator</span>
                </Button>
            }
        >
            <Head title="Evaluators" />

            <div className="mx-auto max-w-5xl space-y-4">
                <p className="text-sm text-gray-500">
                    Each evaluator sits on the panel of <strong>one track</strong> and scores only that
                    track&apos;s papers. Their names appear as columns on that track&apos;s result sheet
                    and in its signature block. Use <strong>Edit</strong> to move an evaluator to another
                    track or reset a forgotten password.
                </p>

                {evaluators.length > 0 && (
                    <div className="flex flex-col gap-3 sm:flex-row">
                        <div className="relative flex-1">
                            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search name, email, or track"
                                className="bg-white pl-9"
                            />
                        </div>
                        <Select
                            value={trackFilter}
                            onChange={(e) => setTrackFilter(e.target.value)}
                            className="sm:w-72"
                            aria-label="Filter by track"
                        >
                            <option value="">All tracks</option>
                            <option value="none">Unassigned{unassignedCount ? ` (${unassignedCount})` : ""}</option>
                            {tracks.map((t) => (
                                <option key={t.id} value={t.id}>
                                    Track {t.number}: {t.name}
                                </option>
                            ))}
                        </Select>
                    </div>
                )}

                {evaluators.length === 0 ? (
                    <div className="rounded-lg border border-dashed bg-white p-12 text-center text-gray-500">
                        <UserPlus className="mx-auto mb-3 size-8 text-gray-300" />
                        No evaluator accounts yet.{" "}
                        <button onClick={openCreate} className="font-semibold text-emerald-700 underline">
                            Add the first evaluator
                        </button>
                        .
                    </div>
                ) : visible.length === 0 ? (
                    <div className="rounded-lg border border-dashed bg-white p-12 text-center text-gray-500">
                        No evaluators match your search.
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[680px] text-sm">
                                <thead className="border-b bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th className="px-4 py-2.5 font-medium">#</th>
                                        <th className="px-4 py-2.5 font-medium">Name</th>
                                        <th className="px-4 py-2.5 font-medium">Email</th>
                                        <th className="px-4 py-2.5 font-medium">Track</th>
                                        <th className="px-4 py-2.5 text-center font-medium">Evaluations</th>
                                        <th className="px-4 py-2.5" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {pager.pageItems.map((u, i) => (
                                        <tr key={u.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-3 text-gray-500">{pager.offset + i + 1}</td>
                                            <td className="px-4 py-3 font-medium text-gray-900">{u.name}</td>
                                            <td className="px-4 py-3 text-gray-700">{u.email}</td>
                                            <td className="px-4 py-3">
                                                {u.track_number ? (
                                                    <span className="inline-flex items-center gap-1.5" title={u.track_name}>
                                                        <Badge variant="outline">T{u.track_number}</Badge>
                                                        <span className="hidden max-w-[220px] truncate text-gray-700 lg:inline">
                                                            {u.track_name}
                                                        </span>
                                                    </span>
                                                ) : (
                                                    <Badge variant="warning">Unassigned</Badge>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <Badge variant={u.evaluations_count > 0 ? "success" : "secondary"}>
                                                    {u.evaluations_count}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        title="Edit or reset password"
                                                        onClick={() => openEdit(u)}
                                                    >
                                                        <Pencil />
                                                        <span className="hidden sm:inline">Edit</span>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Delete"
                                                        className="text-red-600 hover:bg-red-50 hover:text-red-700"
                                                        onClick={() => setDeleting(u)}
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination pager={pager} noun="evaluators" />
                    </div>
                )}
            </div>

            <EvaluatorFormDialog open={formOpen} onOpenChange={setFormOpen} evaluator={editing} tracks={tracks} />

            <ConfirmDialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && !deleteProcessing && setDeleting(null)}
                title={`Remove ${deleting?.name}?`}
                description={
                    deleting?.evaluations_count > 0
                        ? `This evaluator has submitted ${deleting.evaluations_count} evaluation(s). Removing the account will permanently delete those scores and change the averages.`
                        : "The evaluator will no longer be able to sign in. This cannot be undone."
                }
                confirmLabel="Remove evaluator"
                destructive
                processing={deleteProcessing}
                onConfirm={confirmDelete}
            />
        </AppLayout>
    );
}
