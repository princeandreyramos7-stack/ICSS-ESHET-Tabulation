/**
 * Signature block for the panel, printed below result sheets.
 * `chairs` (optional) adds signature lines for the session chair and co-chair.
 */
export default function EvaluatorSignatures({ evaluators = [], chairs = [] }) {
    const namedChairs = chairs.filter((c) => c?.name);
    if (!evaluators.length && !namedChairs.length) return null;

    return (
        <div className="mt-12 break-inside-avoid print:mt-5">
            <p className="mb-8 text-center text-xs font-semibold uppercase tracking-widest text-gray-600 print:mb-5">
                Panel of Evaluators
            </p>
            <div className="grid grid-cols-2 gap-x-8 gap-y-10 sm:grid-cols-3 print:grid-cols-4 print:gap-y-6">
                {evaluators.map((evaluator) => (
                    <div key={evaluator.id} className="flex flex-col items-center">
                        <div className="w-full max-w-[220px] border-t border-black" />
                        <p className="mt-1 text-center text-sm font-medium">{evaluator.name}</p>
                        <p className="text-[11px] text-gray-500">Evaluator</p>
                    </div>
                ))}
            </div>
            {namedChairs.length > 0 && (
                <>
                    <p className="mb-8 mt-10 text-center text-xs font-semibold uppercase tracking-widest text-gray-600 print:mb-5 print:mt-6">
                        Attested by
                    </p>
                    <div className="grid grid-cols-2 gap-x-8 gap-y-10 print:gap-y-6">
                        {namedChairs.map((chair) => (
                            <div key={chair.title} className="flex flex-col items-center">
                                <div className="w-full max-w-[220px] border-t border-black" />
                                <p className="mt-1 text-center text-sm font-medium">{chair.name}</p>
                                <p className="text-[11px] text-gray-500">{chair.title}</p>
                            </div>
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}
