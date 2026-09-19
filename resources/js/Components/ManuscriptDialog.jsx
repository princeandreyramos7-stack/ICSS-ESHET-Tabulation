import { useEffect, useState } from "react";
import { Download, ExternalLink, Eye, FileText } from "lucide-react";

import { Button } from "@/Components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";

/**
 * Entry point for reading a paper's manuscript.
 *
 * Opens as a small "how do you want to read it?" chooser first:
 *   - Open full view   -> the PDF in a new browser tab (plain <a target="_blank">,
 *                         so pop-up blockers never interfere)
 *   - Preview here     -> expands this dialog and embeds the PDF
 *   - Download         -> forced "Save as"
 *
 * `paper` needs: paper_no, title, manuscript_url, manuscript_download_url, manuscript_name.
 */
export default function ManuscriptDialog({ paper, open, onOpenChange }) {
    const [preview, setPreview] = useState(false);

    // Always start on the chooser when (re)opened.
    useEffect(() => {
        if (!open) setPreview(false);
    }, [open]);

    if (!paper?.manuscript_url) return null;

    const title = `Manuscript - Paper ${paper.paper_no}`;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                className={
                    preview
                        ? "flex h-[92vh] max-w-6xl flex-col gap-0 p-0"
                        : "max-w-md"
                }
            >
                {preview ? (
                    <>
                        <DialogHeader className="border-b px-5 py-3 pr-12">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div className="min-w-0">
                                    <DialogTitle className="flex items-center gap-2 text-base">
                                        <FileText className="size-5 shrink-0 text-emerald-600" />
                                        <span className="truncate">{title}</span>
                                    </DialogTitle>
                                    <DialogDescription className="truncate">
                                        {paper.manuscript_name ?? paper.title}
                                    </DialogDescription>
                                </div>
                                <div className="flex gap-2">
                                    <Button asChild variant="outline" size="sm" className="gap-2">
                                        <a href={paper.manuscript_url} target="_blank" rel="noopener noreferrer">
                                            <ExternalLink className="size-4" />
                                            Open full view
                                        </a>
                                    </Button>
                                    <Button asChild variant="ghost" size="sm" className="gap-2">
                                        <a href={paper.manuscript_download_url}>
                                            <Download className="size-4" />
                                            Download
                                        </a>
                                    </Button>
                                </div>
                            </div>
                        </DialogHeader>
                        <div className="min-h-0 flex-1 bg-gray-100">
                            <iframe
                                src={paper.manuscript_url}
                                title={title}
                                className="h-full w-full border-0"
                            />
                        </div>
                        <div className="border-t px-5 py-2 text-xs text-gray-500">
                            If the preview stays blank, your browser blocks embedded PDFs - use
                            <button
                                type="button"
                                className="mx-1 font-medium text-emerald-700 underline"
                                onClick={() => window.open(paper.manuscript_url, "_blank", "noopener")}
                            >
                                Open full view
                            </button>
                            instead.
                        </div>
                    </>
                ) : (
                    <>
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <FileText className="size-5 text-emerald-600" />
                                {title}
                            </DialogTitle>
                            <DialogDescription>
                                {paper.title}
                                {paper.manuscript_name && (
                                    <span className="mt-1 block truncate text-xs text-gray-500">
                                        {paper.manuscript_name}
                                    </span>
                                )}
                            </DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-2">
                            <Button asChild size="lg" className="h-auto justify-start gap-3 py-3">
                                <a href={paper.manuscript_url} target="_blank" rel="noopener noreferrer">
                                    <ExternalLink className="size-5" />
                                    <span className="text-left">
                                        <span className="block font-semibold">Open full view</span>
                                        <span className="block text-xs font-normal opacity-90">
                                            Opens the PDF in a new browser tab
                                        </span>
                                    </span>
                                </a>
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                size="lg"
                                className="h-auto justify-start gap-3 py-3"
                                onClick={() => setPreview(true)}
                            >
                                <Eye className="size-5" />
                                <span className="text-left">
                                    <span className="block font-semibold">Preview here</span>
                                    <span className="block text-xs font-normal text-gray-500">
                                        Read it inside this window without leaving the form
                                    </span>
                                </span>
                            </Button>

                            <Button asChild variant="ghost" size="lg" className="h-auto justify-start gap-3 py-3">
                                <a href={paper.manuscript_download_url}>
                                    <Download className="size-5" />
                                    <span className="text-left">
                                        <span className="block font-semibold">Download PDF</span>
                                        <span className="block text-xs font-normal text-gray-500">
                                            Save a copy to your device
                                        </span>
                                    </span>
                                </a>
                            </Button>
                        </div>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}
