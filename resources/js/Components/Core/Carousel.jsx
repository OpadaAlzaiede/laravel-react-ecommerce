
import { useEffect, useState } from 'react';

export default function Carousel({images}) {

    const [selectedImage, setSelectedImage] = useState(images[0]);

    useEffect(() => {
        setSelectedImage(images[0]);
    }, [images]);

    return (
        <>
            <div className="flex items-start gap-8">
                <div className="flex flex-col items-center gap-2 py-2">
                    {images.map((image, i) => (
                        <button onClick={ev => setSelectedImage(image)}
                                className=
                                    {'border-2 ' +
                                        (selectedImage.id === image.id ? 'border-blue-500' : 'hover:border-blue-500') }
                                key={image.id}>
                            <img src={image.thumb} alt="" className="w-[50px]" />
                        </button>
                    ))}
                </div>
                <div className="carousel w-full">
                    <div className="carousel-item grid min-h-[20px] w-full place-items-center overflow-x-scroll rounded-lg p-6 lg:overflow-visible">
                        <img src={selectedImage?.large} alt="" className="object-cover object-center w-full" />
                    </div>
                </div>
            </div>
        </>
    );
}
