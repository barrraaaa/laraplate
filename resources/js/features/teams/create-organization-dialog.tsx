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
import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';

const schema = z.object({
    name: z.string().min(1).max(255),
});

type FormData = z.infer<typeof schema>;

/**
 * Create-organization dialog — team & hybrid modes (PRD 5.3).
 */
export default function CreateOrganizationDialog() {
    const [open, setOpen] = useState(false);

    const {
        register,
        handleSubmit,
        reset,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<FormData>({
        resolver: zodResolver(schema),
        defaultValues: { name: '' },
    });

    const submit = handleSubmit((data) => {
        router.post('/organizations', data, {
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
                <Button>New organization</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create an organization</DialogTitle>
                    <DialogDescription>
                        Organizations are shared workspaces for your team. You
                        become its owner.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="organization-name">Name</Label>
                        <Input
                            id="organization-name"
                            placeholder="Acme Corp"
                            {...register('name')}
                        />
                        <FormError message={errors.name?.message} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={isSubmitting}>
                            Create organization
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
