import os
from fastapi import FastAPI, Form, HTTPException, UploadFile, File
import logging
from langchain.llms import OpenAI
from langchain.document_loaders import PyPDFLoader
from langchain.vectorstores import Chroma
from langchain.agents.agent_toolkits import create_vectorstore_agent, VectorStoreToolkit, VectorStoreInfo, create_pandas_dataframe_agent
import pandas as pd

# Set API key for OpenAI Service
import os

OPENAI_API_KEY = os.environ.get('OPENAI_API_KEY')


# Create instance of OpenAI LLM
llm = OpenAI(temperature=0.1, verbose=True)

app = FastAPI()

@app.post("/process/pdf/")
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

def csv_tool(filename : str):
    try:
        df = pd.read_csv(filename)
    except Exception as e:
        logging.error("Failed to read CSV file: %s", e)
        raise HTTPException(status_code=500, detail="Failed to read CSV file.")

    return create_pandas_dataframe_agent(OpenAI(temperature=0), df, verbose=True)

def ask_agent(agent, query):
    prompt = (
        """
        Let's decode the way to respond to the queries. The responses depend on the type of information requested in the query...
        """
        + query
    )
    
    try:
        response = agent.run(prompt)
    except Exception as e:
        logging.error("Failed to run prompt: %s", e)
        raise HTTPException(status_code=500, detail="Failed to run prompt.")
        
    return str(response)

@app.post("/process/csv/")
async def process_prompt_csv(prompt: str = Form(...), file: UploadFile = File(...)):
    try:
        file_path = f'C:/laragon/www/python-laravel/public/storage/uploads/{file.filename}'
        with open(file_path, 'wb') as f:
            f.write(await file.read())

        agent = csv_tool(file_path)
        response = ask_agent(agent, prompt)

        return {"response": response}
    except Exception as e:
        logging.error("Failed to process CSV file: %s", e)
        raise

# @app.post("/process/csv/")
# async def process_prompt_csv(prompt: str = Form(...), file: UploadFile = File(...)):
#     try:
#         file_path = f'C:/laragon/www/python-laravel/public/storage/uploads/{file.filename}'
#         with open(file_path, 'wb') as f:
#             f.write(await file.read())

#         # Load the CSV file
#         df = pd.read_csv(file_path)

#         # Convert the DataFrame to a list of strings (one for each row)
#         rows = df.apply(lambda row: ', '.join(row.astype(str)), axis=1).tolist()

#         # Load documents into vector database aka ChromaDB
#         store = Chroma.from_documents(rows, collection_name=file.filename)
       
#         # Create vectorstore info object
#         vectorstore_info = VectorStoreInfo( 
#             name=file.filename,
#             description="",
#             vectorstore=store
#         )

#         # Convert the document store into a langchain toolkit
#         toolkit = VectorStoreToolkit(vectorstore_info=vectorstore_info)

#         # Add the toolkit to an end-to-end LC
#         agent_executor = create_vectorstore_agent(
#             llm=llm,
#             toolkit=toolkit,
#             verbose=True
#         )

#         # Pass the prompt to the LLM
#         response = agent_executor.run(prompt)

#         # Find the relevant pages
#         search = store.similarity_search_with_score(prompt)
#         search_results = [result[0].page_content for result in search]

#         return {"response": response, "search_results": search_results}
#     except Exception as e:
#         logging.error("Failed to process prompt: %s", e)
#         raise


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=8888)
