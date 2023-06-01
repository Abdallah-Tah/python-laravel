import os
from fastapi import FastAPI, Form, HTTPException, UploadFile, File
import logging
from langchain.llms import OpenAI
from langchain.document_loaders import PyPDFLoader
from langchain.vectorstores import Chroma
from langchain.agents.agent_toolkits import create_vectorstore_agent, VectorStoreToolkit, VectorStoreInfo

# Set API key for OpenAI Service
os.environ['OPENAI_API_KEY'] = 'sk-'

# Create instance of OpenAI LLM
llm = OpenAI(temperature=0.1, verbose=True)

app = FastAPI()

@app.post("/process_prompt")
async def process_prompt(prompt: str = Form(...), file: UploadFile = File(...)):
    try:
        file_path = f'C:/laragon/www/python-laravel/public/storage/uploads/{file.filename}'
        with open(file_path, 'wb') as f:
            f.write(await file.read())

        # Load the PDF file
        loader = PyPDFLoader(file_path)
        pages = loader.load_and_split()

        # Load documents into vector database aka ChromaDB
        store = Chroma.from_documents(pages, collection_name=file.filename)

        # Create vectorstore info object
        vectorstore_info = VectorStoreInfo(
            name=file.filename,
            description="",
            vectorstore=store
        )

        # Convert the document store into a langchain toolkit
        toolkit = VectorStoreToolkit(vectorstore_info=vectorstore_info)

        # Add the toolkit to an end-to-end LC
        agent_executor = create_vectorstore_agent(
            llm=llm,
            toolkit=toolkit,
            verbose=True
        )

        # Pass the prompt to the LLM
        response = agent_executor.run(prompt)

        # Find the relevant pages
        search = store.similarity_search_with_score(prompt)
        search_results = [result[0].page_content for result in search]

        return {"response": response, "search_results": search_results}
    except Exception as e:
        logging.error("Failed to process prompt: %s", e)
        raise


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=8002)
