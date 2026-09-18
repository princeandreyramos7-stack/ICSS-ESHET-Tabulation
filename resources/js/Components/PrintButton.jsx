import { Printer } from "lucide-react";

import { Button } from "@/Components/ui/button";

export default function PrintButton({ label = "Print" }) {
    return (
        <Button
            type="button"
            variant="outline"
            onClick={() => window.print()}
            className="print:hidden"
        >
            <Printer />
            {label}
        </Button>
    );
}
