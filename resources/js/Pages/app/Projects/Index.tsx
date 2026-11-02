import AppLayout from '@/components/layouts/app-layout';
import { Card, CardContent } from '@/components/ui/card';
import CreateProjectDialog from '@/features/projects/create-project-dialog';
import ProjectCard from '@/features/projects/project-card';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, Inbox } from 'lucide-react';

type ProjectItem = {
    id: number;
    name: string;
    description: string | null;
    status: string;
    creator: string | null;
    createdAt: string;
};

type ProjectsProps = {
    projects: ProjectItem[];
    usage: { used: number; limit: number | null };
    canCreate: boolean;
};

/**
 * Example module index (PRD 5.12) — replace this slice with your own
 * product; projects.show etc. are intentionally the reference point.
 */
export default function Index() {
    const { projects, usage, canCreate } =
        usePage<PageProps<ProjectsProps>>().props;

    const atLimit = usage.limit !== null && usage.used >= usage.limit;

    return (
        <AppLayout>
            <Head title="Projects" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Projects
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Example module — scoped to your organization and
                            capped by your plan.
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <span className="text-muted-foreground text-xs">
                            {usage.used} /{' '}
                            {usage.limit === null ? '∞' : usage.limit} used
                        </span>
                        {canCreate && <CreateProjectDialog />}
                    </div>
                </div>

                {atLimit && (
                    <div className="flex items-center gap-3 rounded-lg border border-amber-600/40 bg-amber-500/10 px-4 py-3 text-sm">
                        <AlertTriangle className="size-4 shrink-0 text-amber-600 dark:text-amber-400" />
                        <span>
                            You&apos;ve reached the projects limit for your
                            plan.{' '}
                            <Link
                                href={route('billing.show')}
                                className="font-medium underline underline-offset-4"
                            >
                                Upgrade to add more
                            </Link>
                            .
                        </span>
                    </div>
                )}

                {projects.length === 0 ? (
                    <Card className="border-dashed">
                        <CardContent className="flex flex-col items-center py-16 text-center">
                            <Inbox className="text-muted-foreground size-8" />
                            <h3 className="mt-3 text-sm font-semibold">
                                No projects yet
                            </h3>
                            <p className="text-muted-foreground mt-1 max-w-sm text-sm">
                                Create your first project — it&apos;s the module
                                you&apos;ll replace with your own product
                                feature.
                            </p>
                            {canCreate && (
                                <div className="mt-4">
                                    <CreateProjectDialog />
                                </div>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {projects.map((project) => (
                            <ProjectCard
                                key={project.id}
                                project={project}
                                canManage={canCreate}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
