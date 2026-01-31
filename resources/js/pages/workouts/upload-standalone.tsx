import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { AlertCircle, Camera, Image as ImageIcon, X } from 'lucide-react';
import { useRef, useState } from 'react';

export default function WorkoutUploadStandalone() {
    const { flash } = usePage<SharedData>().props;
    const [selectedImage, setSelectedImage] = useState<string | null>(null);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [isUploading, setIsUploading] = useState(false);
    // Initialize error state with flash error if present
    const [error, setError] = useState<string | null>(flash?.error ?? null);

    const cameraInputRef = useRef<HTMLInputElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleImageSelect = (file: File) => {
        setError(null);

        // Validate file type
        if (!file.type.startsWith('image/')) {
            setError('Please select a valid image file');
            return;
        }

        // Validate file size (max 10MB)
        const maxSize = 10 * 1024 * 1024;
        if (file.size > maxSize) {
            setError('Image size must be less than 10MB');
            return;
        }

        setSelectedFile(file);

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            setSelectedImage(e.target?.result as string);
        };
        reader.readAsDataURL(file);
    };

    const handleCameraCapture = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            handleImageSelect(file);
        }
    };

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            handleImageSelect(file);
        }
    };

    const handleRetake = () => {
        setSelectedImage(null);
        setSelectedFile(null);
        setError(null);
    };

    const handleUpload = async () => {
        if (!selectedFile) return;

        setIsUploading(true);
        setError(null);

        try {
            const formData = new FormData();
            formData.append('photo', selectedFile);

            // Use Inertia's router to submit the form
            router.post('/api/workouts/upload', formData, {
                forceFormData: true,
                onError: (errors) => {
                    // Handle various error formats from Inertia/Laravel
                    const errorMessage = 
                        (errors as Record<string, string>).photo ||
                        (errors as Record<string, string>).message ||
                        (typeof errors === 'string' ? errors : null) ||
                        'Failed to upload photo. Please try again.';
                    setError(errorMessage);
                },
                onFinish: () => {
                    setIsUploading(false);
                },
            });
        } catch (err) {
            console.error('Upload error:', err);
            setError('An unexpected error occurred. Please try again.');
            setIsUploading(false);
        }
    };

    return (
        <>
            <Head title="Upload Workout" />

            <div className="flex min-h-screen flex-col bg-background">
                {/* Preview Mode - Full Screen */}
                {selectedImage ? (
                    <div className="fixed inset-0 z-50 flex flex-col bg-black">
                        {/* Header */}
                        <div className="flex items-center justify-between bg-black/80 p-4">
                            <h2 className="text-lg font-semibold text-white">
                                Preview
                            </h2>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={handleRetake}
                                disabled={isUploading}
                                className="text-white hover:bg-white/20"
                            >
                                <X className="size-5" />
                            </Button>
                        </div>

                        {/* Image Preview */}
                        <div className="flex flex-1 items-center justify-center overflow-hidden p-4">
                            <img
                                src={selectedImage}
                                alt="Workout preview"
                                className="max-h-full max-w-full object-contain"
                            />
                        </div>

                        {/* Error Alert */}
                        {error && (
                            <div className="px-4 pb-4">
                                <Alert variant="destructive">
                                    <AlertCircle />
                                    <AlertTitle>Error</AlertTitle>
                                    <AlertDescription>{error}</AlertDescription>
                                </Alert>
                            </div>
                        )}

                        {/* Bottom Actions - Thumb Zone */}
                        <div className="flex flex-col gap-3 bg-black/80 p-4 pb-8">
                            <Button
                                size="lg"
                                onClick={handleUpload}
                                disabled={isUploading}
                                className="h-14 w-full text-base font-semibold"
                            >
                                {isUploading ? (
                                    <>
                                        <span className="animate-pulse">
                                            Processing...
                                        </span>
                                    </>
                                ) : (
                                    'Use This Photo'
                                )}
                            </Button>
                            <Button
                                size="lg"
                                variant="outline"
                                onClick={handleRetake}
                                disabled={isUploading}
                                className="h-14 w-full text-base"
                            >
                                Retake
                            </Button>
                        </div>
                    </div>
                ) : (
                    /* Capture Mode */
                    <div className="flex flex-1 flex-col items-center justify-center gap-6 p-6">
                        <Card className="w-full max-w-md">
                            <CardHeader className="text-center">
                                <CardTitle className="text-2xl">
                                    Upload Workout Photo
                                </CardTitle>
                                <CardDescription>
                                    Take a photo of your handwritten workout log
                                    or select from gallery
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                {/* Camera Capture Button - Primary */}
                                <Button
                                    size="lg"
                                    onClick={() =>
                                        cameraInputRef.current?.click()
                                    }
                                    className="h-16 w-full text-base font-semibold"
                                >
                                    <Camera className="size-6" />
                                    Take Photo
                                </Button>

                                {/* Hidden camera input */}
                                <input
                                    ref={cameraInputRef}
                                    type="file"
                                    accept="image/*"
                                    capture="environment"
                                    onChange={handleCameraCapture}
                                    className="hidden"
                                />

                                {/* Divider */}
                                <div className="relative">
                                    <div className="absolute inset-0 flex items-center">
                                        <span className="w-full border-t" />
                                    </div>
                                    <div className="relative flex justify-center text-xs uppercase">
                                        <span className="bg-card px-2 text-muted-foreground">
                                            Or
                                        </span>
                                    </div>
                                </div>

                                {/* File Upload Button - Secondary */}
                                <Button
                                    size="lg"
                                    variant="outline"
                                    onClick={() =>
                                        fileInputRef.current?.click()
                                    }
                                    className="h-16 w-full text-base"
                                >
                                    <ImageIcon className="size-6" />
                                    Choose from Gallery
                                </Button>

                                {/* Hidden file input */}
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/*"
                                    onChange={handleFileSelect}
                                    className="hidden"
                                />
                            </CardContent>
                        </Card>

                        {/* Error Alert */}
                        {error && (
                            <Alert variant="destructive" className="max-w-md">
                                <AlertCircle />
                                <AlertTitle>Error</AlertTitle>
                                <AlertDescription>{error}</AlertDescription>
                            </Alert>
                        )}

                        {/* Tips Card */}
                        <Card className="max-w-md">
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Tips for Best Results
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul className="space-y-2 text-sm text-muted-foreground">
                                    <li>• Ensure good lighting</li>
                                    <li>• Keep text clear and readable</li>
                                    <li>• Avoid shadows and glare</li>
                                    <li>• Capture the entire workout log</li>
                                </ul>
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </>
    );
}

