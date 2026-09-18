import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/Components/ui/alert-dialog";

/**
 * Generic confirmation dialog. `children` renders inside the description
 * area so callers can show a summary before the user commits.
 */
export default function ConfirmDialog({
    open,
    onOpenChange,
    title,
    description,
    confirmLabel = "Confirm",
    cancelLabel = "Cancel",
    destructive = false,
    processing = false,
    onConfirm,
    children,
}) {
    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent className="max-h-[90vh] max-w-lg overflow-y-auto">
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    {description && (
                        <AlertDialogDescription>{description}</AlertDialogDescription>
                    )}
                </AlertDialogHeader>
                {children}
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>{cancelLabel}</AlertDialogCancel>
                    <AlertDialogAction
                        disabled={processing}
                        onClick={(e) => {
                            e.preventDefault();
                            onConfirm?.();
                        }}
                        className={
                            destructive
                                ? "bg-red-600 text-white hover:bg-red-700"
                                : "bg-emerald-700 text-white hover:bg-emerald-800"
                        }
                    >
                        {processing ? "Please wait..." : confirmLabel}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
