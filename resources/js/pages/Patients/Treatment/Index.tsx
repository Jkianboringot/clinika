import { Head, Link } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Treatment = {
    id: number;
    follow_up_schedule: string | null;
    duration_minutes: number | null;
    summary: string | null;
    user: { id: number; name: string } | null; // dentist
    appointment: {
        id: number;
        appointment_date: string;
        appointment_time: string;
        procedure: { id: number; name: string } | null;
    } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'My Treatments', href: '/patient/treatments' }];

const formatDate = (d: string) =>
    new Date(d + 'T00:00:00').toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

export default function PatientTreatmentsIndex({ treatments }: { treatments: Paginated<Treatment> }) {
    return (
     <>
            <Head title="My Treatments" />

            <div className="p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>My Treatment Summaries</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Procedure</TableHead>
                                    <TableHead>Dentist</TableHead>
                                    <TableHead>Duration</TableHead>
                                    <TableHead>Follow-up</TableHead>
                                    <TableHead>Summary</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {treatments.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                            No treatment records yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {treatments.data.map((t) => (
                                    <TableRow key={t.id}>
                                        <TableCell>{t.appointment ? formatDate(t.appointment.appointment_date) : '-'}</TableCell>
                                        <TableCell className="font-medium">{t.appointment?.procedure?.name ?? '-'}</TableCell>
                                        <TableCell>{t.user ? `Dr. ${t.user.name.replace(/^Dr\.?\s*/i, '')}` : '-'}</TableCell>
                                        <TableCell>{t.duration_minutes ? `${t.duration_minutes} min` : '-'}</TableCell>
                                        <TableCell>
                                            {t.follow_up_schedule ? (
                                                <span className="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:border-blue-500/30 dark:bg-blue-500/15 dark:text-blue-300">
                                                    <CalendarClock className="h-3.5 w-3.5" />
                                                    {formatDate(t.follow_up_schedule)}
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground">None</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="max-w-xs">
                                            {t.summary ? (
                                                <span className="line-clamp-2" title={t.summary}>
                                                    {t.summary}
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground">No summary yet</span>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        <div className="mt-4 flex flex-wrap gap-1">
                            {treatments.links.map((link, i) =>
                                link.url ? (
                                    <Button key={i} asChild size="sm" variant={link.active ? 'default' : 'outline'}>
                                        <Link href={link.url} preserveScroll dangerouslySetInnerHTML={{ __html: link.label }} />
                                    </Button>
                                ) : (
                                    <Button key={i} size="sm" variant="outline" disabled dangerouslySetInnerHTML={{ __html: link.label }} />
                                ),
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
      </>
    );
}