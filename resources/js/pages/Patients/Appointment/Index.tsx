import { Head, Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useCan } from '@/hooks/use-can';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Appointment = {
    id: number;
    appointment_date: string;
    appointment_time: string;
    status: string;
    description: string | null;
    procedure: { id: number; name: string } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'My Appointments', href: '/patient/appointments' }];

const statusStyles: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:border-amber-500/30',
    confirmed: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500/30',
    scheduled: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500/30',
    completed: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-500/15 dark:text-green-300 dark:border-green-500/30',
    cancelled: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-500/15 dark:text-red-300 dark:border-red-500/30',
};

function StatusBadge({ status }: { status: string }) {
    const key = status?.toLowerCase();
    const style = statusStyles[key] ?? 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-500/15 dark:text-gray-300 dark:border-gray-500/30';
    return (
        <Badge variant="outline" className={`rounded-full px-2.5 py-0.5 font-medium capitalize ${style}`}>
            <span className="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-current" />
            {status}
        </Badge>
    );
}

const formatDate = (d: string) =>
    new Date(d + 'T00:00:00').toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

const formatTime = (t: string) => {
    const [h, m] = t.split(':').map(Number);
    return new Date(2000, 0, 1, h, m).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
};

export default function PatientAppointmentsIndex({ appointments }: { appointments: Paginated<Appointment> }) {
    const can = useCan();
    const { flash } = usePage<{ flash?: { success?: string } }>().props;

    return (
        <>

            <div className="flex flex-col gap-4 p-4">
                {flash?.success && (
                    <div className="rounded-md border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">{flash.success}</div>
                )}

                <Card>
                   
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Time</TableHead>
                                    <TableHead>Procedure</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {appointments.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                                            No appointments yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {appointments.data.map((a) => (
                                    <TableRow key={a.id}>
                                        <TableCell>{formatDate(a.appointment_date)}</TableCell>
                                        <TableCell>{formatTime(a.appointment_time)}</TableCell>
                                        <TableCell className="font-medium">{a.procedure?.name ?? '-'}</TableCell>
                                        <TableCell className="max-w-xs truncate text-muted-foreground" title={a.description ?? ''}>
                                            {a.description ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge status={a.status} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        <div className="mt-4 flex flex-wrap gap-1">
                            {appointments.links.map((link, i) =>
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