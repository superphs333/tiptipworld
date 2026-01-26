import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link, useForm } from '@inertiajs/react';

// Form to edit an existing tip (owner only).
export default function Edit({ tip }) {
    const tipData = tip?.data ?? tip;
    const tipId = tipData?.id;
    const { data, setData, put, processing, errors } = useForm({
        title: tipData?.title ?? '',
        content: tipData?.content ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        if (!tipId) {
            return;
        }
        put(route('tips.update', tipId));
    };

    return (
        <PublicLayout>
            <Head title="팁 수정" />

            <div className="mx-auto max-w-4xl">
                <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form onSubmit={submit} className="space-y-6 p-6">
                        <div>
                            <InputLabel htmlFor="title" value="제목" />
                            <TextInput
                                id="title"
                                className="mt-1 block w-full"
                                value={data.title}
                                onChange={(e) =>
                                    setData('title', e.target.value)
                                }
                                maxLength={255}
                                required
                                isFocused
                            />
                            <InputError className="mt-2" message={errors.title} />
                        </div>

                        <div>
                            <InputLabel htmlFor="content" value="내용" />
                            <textarea
                                id="content"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={data.content}
                                onChange={(e) =>
                                    setData('content', e.target.value)
                                }
                                rows={8}
                                required
                            />
                            <InputError
                                className="mt-2"
                                message={errors.content}
                            />
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <Link
                                href={
                                    tipId
                                        ? route('tips.show', tipId)
                                        : route('tips.index')
                                }
                                className="text-sm font-semibold text-gray-600 hover:text-gray-900"
                            >
                                ← 상세로
                            </Link>
                            <PrimaryButton disabled={processing}>
                                수정하기
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </PublicLayout>
    );
}
