import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link, useForm } from '@inertiajs/react';

// Form to create a new tip (auth required).
export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        content: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('tips.store'));
    };

    return (
        <PublicLayout>
            <Head title="팁 작성" />

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
                            <InputError
                                className="mt-2"
                                message={errors.title}
                            />
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
                                href={route('tips.index')}
                                className="text-sm font-semibold text-gray-600 hover:text-gray-900"
                            >
                                ← 목록으로
                            </Link>
                            <PrimaryButton disabled={processing}>
                                등록하기
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </PublicLayout>
    );
}
