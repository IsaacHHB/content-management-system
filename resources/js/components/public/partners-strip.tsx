export type PublicPartner = {
    id: number;
    name: string;
    website_url: string | null;
    logo_url: string | null;
};

export function PartnersStrip({ partners }: { partners: PublicPartner[] }) {
    if (partners.length === 0) {
        return null;
    }

    return (
        <section
            aria-labelledby="partners-heading"
            className="border-t bg-white"
        >
            <div className="mx-auto max-w-6xl px-4 py-10">
                <h2
                    id="partners-heading"
                    className="text-center text-sm font-semibold tracking-wide text-neutral-500 uppercase"
                >
                    Our Partners &amp; Funders
                </h2>
                <ul className="mt-8 flex flex-wrap items-start justify-center gap-x-10 gap-y-8">
                    {partners.map((partner) => {
                        // The name caption guarantees every partner is legible
                        // even when its logo is a white-knockout mark (a
                        // transparent PNG meant for dark backgrounds) that is
                        // invisible on this light strip.
                        const inner = (
                            <span className="flex w-32 flex-col items-center gap-2 text-center">
                                {partner.logo_url && (
                                    <span className="flex h-14 w-full items-center justify-center">
                                        <img
                                            src={partner.logo_url}
                                            alt={partner.name}
                                            loading="lazy"
                                            className="max-h-12 w-auto max-w-full object-contain"
                                        />
                                    </span>
                                )}
                                <span className="text-xs font-medium text-neutral-600">
                                    {partner.name}
                                </span>
                            </span>
                        );

                        return (
                            <li key={partner.id}>
                                {partner.website_url ? (
                                    <a
                                        href={partner.website_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="transition hover:opacity-80"
                                    >
                                        {inner}
                                    </a>
                                ) : (
                                    inner
                                )}
                            </li>
                        );
                    })}
                </ul>
            </div>
        </section>
    );
}
