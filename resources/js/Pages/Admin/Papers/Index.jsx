import { useEffect, useMemo, useState } from "react";
import { Head, router, useForm } from "@inertiajs/react";
import { Pencil, Plus, Search, Trash2 } from "lucide-react";

import ConfirmDialog from "@/Components/ConfirmDialog";
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
import AppLayout from "@/Layouts/AppLayout";

const emptyPaper = (trackId = "") => ({
    track_id: trackId,
    paper_no: "",
    title: "",
    researcher: "",
    affiliation: "",
    presentation_order: "",
});

function PaperFormDialog({ open, onOpenChange, paper, tracks, defaultTrackId }) {
    const isEdit = Boolean(paper?.id);
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm(
        emptyPaper(defaultTrackId)
    );

    useEffect(() => {
        if (!open) return;
        clearErrors();
        if (paper) {
            setData({
                track_id: String(paper.track_id),
                paper_no: paper.paper_no,
                title: paper.title,
                researcher: paper.researcher,
                affiliation: paper.affiliation ?? "",
                presentation_order: paper.presentation_order ?? "",
            });
        } else {
            setData(emptyPaper(defaultTrackId ? String(defaultTrackId) : String(tracks[0]?.id ?? "")));
        }
    }, [open, paper]);

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
            put(route("admin.papers.update", paper.id), options);
        } else {
            post(route("admin.papers.store"), options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={(o) => !processing && onOpenChange(o)}>
            <DialogContent className="max-h-[92vh] overflow-y-auto sm:max-w-lg">
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>{isEdit ? `Edit Paper ${paper.paper_no}` : "Add Paper"}</DialogTitle>
                        <DialogDescription>
                            Paper number must be unique across all tracks.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="mt-4 grid gap-4">
                        <div className="grid gap-1.5">
                            <Label htmlFor="track_id">Track</Label>
                            <Select
                                id="track_id"
                                value={data.track_id}
                                onChange={(e) => setData("track_id", e.target.value)}
                                required
                            >
                                {tracks.map((t) => (
                                    <option key={t.id} value={t.id}>
                                        Track {t.number}: {t.name}
                                    </option>
                                ))}
                            </Select>
                            <InputError message={errors.track_id} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-[1fr_140px]">
                            <div className="grid gap-1.5">
                                <Label htmlFor="paper_no">Paper No.</Label>
                                <Input
                                    id="paper_no"
                                    value={data.paper_no}
                                    onChange={(e) => setData("paper_no", e.target.value)}
                                    placeholder="e.g. T4-001"
                                    maxLength={50}
                                    required
                                    autoFocus
                                />
                                <InputError message={errors.paper_no} />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="presentation_order">Order</Label>
                                <Input
                                    id="presentation_order"
                                    type="number"
                                    min="1"
                                    max="999"
                                    value={data.presentation_order}
                                    onChange={(e) => setData("presentation_order", e.target.value)}
                                    placeholder="Optional"
                                />
                                <InputError message={errors.presentation_order} />
                            </div>
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="title">Research title</Label>
                            <Input
                                id="title"
                                value={data.title}
                                onChange={(e) => setData("title", e.target.value)}
                                maxLength={255}
                                required
                            />
                            <InputError message={errors.title} />
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="researcher">Researcher / presenter</Label>
                            <Input
                                id="researcher"
                                value={data.researcher}
                                onChange={(e) => setData("researcher", e.target.value)}
                                maxLength={255}
                                required
                            />
                            <InputError message={errors.researcher} />
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="affiliation">Affiliation (optional)</Label>
                            <Input
                                id="affiliation"
                                value={data.affiliation}
                                onChange={(e) => setData("affiliation", e.target.value)}
                                maxLength={255}
                            />
                            <InputError message={errors.affiliation} />
                        </div>
                    </div>

                    <DialogFooter className="mt-6">
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={processing}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing} className="bg-emerald-700 hover:bg-emerald-800">
                            {processing ? "Saving..." : isEdit ? "Save changes" : "Add paper"}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function Index({ papers, tracks, filters }) {
    const [search, setSearch] = useState(filters.search ?? "");
    const [trackFilter, setTrackFilter] = useState(filters.track ? String(filters.track) : "");
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [deleteProcessing, setDeleteProcessing] = useState(false);

    // Filtering happens client-side; the full list is small (conference scale).
    const visible = useMemo(() => {
        const q = search.trim().toLowerCase();
        return papers.filter((p) => {
            if (trackFilter && String(p.track_id) !== trackFilter) return false;
            if (!q) return true;
            return (
                p.paper_no.toLowerCase().includes(q) ||
                p.title.toLowerCase().includes(q) ||
                p.researcher.toLowerCase().includes(q)
            );
        });
    }, [papers, search, trackFilter]);

    const grouped = useMemo(() => {
        const map = new Map();
        tracks.forEach((t) => map.set(t.id, { track: t, papers: [] }));
        visible.forEach((p) => map.get(p.track_id)?.papers.push(p));
        return Array.from(map.values()).filter((g) => g.papers.length > 0);
    }, [visible, tracks]);

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };
    const openEdit = (paper) => {
        setEditing(paper);
        setFormOpen(true);
    };

    const confirmDelete = () => {
        if (!deleting) return;
        setDeleteProcessing(true);
        router.delete(route("admin.papers.destroy", deleting.id), {
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
                { label: "Papers" },
            ]}
            actions={
                <Button onClick={openCreate} className="bg-emerald-700 hover:bg-emerald-800">
                    <Plus />
                    <span className="hidden sm:inline">Add paper</span>
                </Button>
            }
        >
            <Head title="Papers" />

            <div className="mx-auto max-w-6xl space-y-4">
                <div className="flex flex-col gap-3 sm:flex-row">
                    <div className="relative flex-1">
                        <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search paper no., title, or researcher"
                            className="bg-white pl-9"
                        />
                    </div>
                    <Select
                        value={trackFilter}
                        onChange={(e) => setTrackFilter(e.target.value)}
                        className="sm:w-72"
                    >
                        <option value="">All tracks</option>
                        {tracks.map((t) => (
                            <option key={t.id} value={t.id}>
                                Track {t.number}: {t.name}
                            </option>
                        ))}
                    </Select>
                </div>

                <p className="text-sm text-gray-500">
                    Showing {visible.length} of {papers.length} papers
                </p>

                {grouped.length === 0 ? (
                    <div className="rounded-lg border border-dashed bg-white p-12 text-center text-gray-500">
                        {papers.length === 0 ? (
                            <>
                                No papers yet.{" "}
                                <button onClick={openCreate} className="font-semibold text-emerald-700 underline">
                                    Add the first paper
                                </button>
                                .
                            </>
                        ) : (
                            "No papers match your search."
                        )}
                    </div>
                ) : (
                    grouped.map(({ track, papers: list }) => (
                        <div key={track.id} className="overflow-hidden rounded-lg bg-white shadow-sm">
                            <div className="flex items-center justify-between border-b bg-gray-50 px-4 py-2.5">
                                <h3 className="font-semibold text-gray-800">
                                    Track {track.number}: {track.name}
                                </h3>
                                <Badge variant="secondary">{list.length}</Badge>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[640px] text-sm">
                                    <thead className="text-left text-xs uppercase tracking-wide text-gray-500">
                                        <tr>
                                            <th className="px-4 py-2 font-medium">#</th>
                                            <th className="px-4 py-2 font-medium">Paper No.</th>
                                            <th className="px-4 py-2 font-medium">Title</th>
                                            <th className="px-4 py-2 font-medium">Researcher</th>
                                            <th className="px-4 py-2 text-center font-medium">Evaluations</th>
                                            <th className="px-4 py-2" />
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {list.map((p) => (
                                            <tr key={p.id} className="hover:bg-gray-50">
                                                <td className="px-4 py-2.5 text-gray-500">
                                                    {p.presentation_order ?? "-"}
                                                </td>
                                                <td className="px-4 py-2.5 font-bold text-gray-900">{p.paper_no}</td>
                                                <td className="px-4 py-2.5 text-gray-900">{p.title}</td>
                                                <td className="px-4 py-2.5 text-gray-700">
                                                    {p.researcher}
                                                    {p.affiliation && (
                                                        <span className="block text-xs text-gray-500">{p.affiliation}</span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-2.5 text-center">
                                                    <Badge variant={p.evaluations_count > 0 ? "success" : "secondary"}>
                                                        {p.evaluations_count}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-2.5">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            title="Edit"
                                                            onClick={() => openEdit(p)}
                                                        >
                                                            <Pencil />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            title="Delete"
                                                            className="text-red-600 hover:bg-red-50 hover:text-red-700"
                                                            onClick={() => setDeleting(p)}
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
                        </div>
                    ))
                )}
            </div>

            <PaperFormDialog
                open={formOpen}
                onOpenChange={setFormOpen}
                paper={editing}
                tracks={tracks}
                defaultTrackId={trackFilter}
            />

            <ConfirmDialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && !deleteProcessing && setDeleting(null)}
                title={`Delete paper ${deleting?.paper_no}?`}
                description={
                    deleting?.evaluations_count > 0
                        ? `This paper already has ${deleting.evaluations_count} submitted evaluation(s). Deleting it will permanently remove those scores as well.`
                        : "This cannot be undone."
                }
                confirmLabel="Delete"
                destructive
                processing={deleteProcessing}
                onConfirm={confirmDelete}
            />
        </AppLayout>
    );
}
