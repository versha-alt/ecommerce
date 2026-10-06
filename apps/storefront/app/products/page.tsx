import {catalog} from '@/lib/store';import {filter} from '@/lib/filter';import Listing from '@/components/listing';
export const metadata={title:'Shop all products'};
export default async function Products({searchParams}:{searchParams:Promise<Record<string,string>>}){const data=await catalog();const query=await searchParams;return <Listing data={data} products={filter(data.products,query)} title="Your everyday, upgraded." description="Find hardworking appliances from the brands you love. A good fit for every room and every routine."/>}
