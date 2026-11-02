import FormError from '@/components/shared/form-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';

const schema = z.object({
    name: z.string().min(1).max(255),
    description: z.string().max(5000).optional(),
});

type FormData = z.infer<typeof schema>;

/**
 * Example module: create dialog, with the quota error surfaced from the
 * QuotaExceededException renderer.
 */
export default function CreateProjectDialog() {
    const [open, setOpen] = useState(false);

    const {
        register,
        handleSubmit,
        reset,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<FormData>({
        resolver: zodResolver(schema),
        defaultValues: { name: '', description: '' },
    });

    const submit = handleSubmit((data) => {
        router.post(route('projects.store'), data, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
            onError: (serverErrors) => {
                if (serverErrors.name) {
                    setError('name', { message: serverErrors.name });
                }
            },
        });
    });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm">New project</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create a project</DialogTitle>
                    <DialogDescription>
                        Projects demonstrate the full extension pattern:
                        tenancy, policy and quota enforcement.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="project-name">Name</Label>
                        <Input
                            id="project-name"
                            placeholder="Launch site"
                            {...register('name')}
                        />
                        <FormError message={errors.name?.message} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="project-description">Description</Label>
                        <Textarea
                            id="project-description"
                            placeholder="What is this project about?"
                            {...register('description')}
                        />
                        <FormError message={errors.description?.message} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={isSubmitting}>
                            Create project
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
