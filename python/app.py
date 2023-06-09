import os
from typing import Dict, List
from fastapi import FastAPI
from dotenv import load_dotenv
from langchain import HuggingFaceHub, PromptTemplate, LLMChain
from typing import List, Dict

import uvicorn

# Load environment variables from .env file
load_dotenv()
huggingfacehub_api_token = os.getenv("HUGGINGFACEHUB_API_TOKEN")

app = FastAPI()
repo_id = "tiiuae/falcon-7b-instruct"

# Initialize HuggingFaceHub instance
llm = HuggingFaceHub(
    huggingfacehub_api_token=huggingfacehub_api_token,
    repo_id=repo_id,
    model_kwargs={"temperature": 0.6, "max_new_tokens": 500}
)

# Define the prompt template
template = """
You are an artificial intelligence assistant. The assistant gives helpful, detailed, and polite answers to the questions.

{dialogue}

Assistant:"""


# Initialize PromptTemplate and LLMChain instances
prompt = PromptTemplate(template=template, input_variables=["dialogue"])
llm_chain = LLMChain(prompt=prompt, llm=llm)

@app.post("/process/chatbot")
async def generate_response(data: Dict[str, List[Dict[str, str]]]):
    dialogue = data.get("dialogue")
    formatted_dialogue = "\n".join([f"{turn['role']}: {turn['content']}" for turn in dialogue])
    response = llm_chain.run(formatted_dialogue)
    
    # Remove trailing "user:"
    #response = response.rstrip("user:")

    return {"assistant": response}


if __name__ == "__main__":
    uvicorn.run(app, host="127.0.0.1", port=8888)
