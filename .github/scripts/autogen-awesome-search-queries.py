import os
import argparse
from ruamel.yaml import YAML
import logging
import io

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

# Create a YAML object
yaml = YAML()
yaml.indent(mapping=2, sequence=4, offset=2)
yaml.preserve_quotes = True
yaml.width = 10000000  # Set a large value to prevent word wrapping

# Define the query fields
query_fields = [
    "censys-query",
    "fofa-query",
    "github-query",
    "google-query",
    "hunter-query",
    "publicwww-query",
    "shodan-query",
    "zoomeye-query"
]

def read_yaml_files(directory):
    yaml_files = []
    for root, dirs, files in os.walk(directory):
        for file in files:
            if file.endswith(".yaml"):
                yaml_files.append(os.path.join(root, file))
    return yaml_files

def normalize_query(query):
    """Normalize query to ensure case-insensitivity does not lead to duplicates."""
    return query.lower()

def map_query(query, existing_queries):
    if '||' in query or '&&' in query:
        return {}

    if query.startswith("http.title:") or query.startswith("title:"):
        query = query.split(":", 1)[1].strip()
        if f"http.title:{query}" not in existing_queries:
            return {
                "shodan": [f"http.title:{query}"],
                "fofa": [f"title={query}"],
                "google": [f"intitle:{query}"]
            }
    elif query.startswith("http.html:") or query.startswith("html:") or query.startswith("body="):
        query = query.split(":", 1)[1].strip() if ":" in query else query.split("=", 1)[1].strip()
        if f"http.html:{query}" not in existing_queries:
            return {
                "shodan": [f"http.html:{query}"],
                "fofa": [f"body={query}"]
            }
    elif query.startswith("http.favicon.hash:") or query.startswith("icon_hash="):
        query = query.split(":", 1)[1].strip() if ":" in query else query.split("=", 1)[1].strip()
        return {
            "shodan": [f"http.favicon.hash:{query}"],
            "fofa": [f"icon_hash={query}"]
        }
    else:
        return {}

def map_publicwww_query(query):
    return {
        "shodan": [f"http.html:{query}"],
        "fofa": [f"body={query}"]
    }

def load_ignore_list(ignore_file):
    ignore_list = set()
    if os.path.exists(ignore_file):
        with open(ignore_file, 'r') as file:
            ignore_list = set(line.strip().lower() for line in file if line.strip())
    return ignore_list

def should_ignore_query(platform, query, ignore_list):
    normalized_query = f'{platform}:{query}'.lower()
    return any(ignore in normalized_query for ignore in ignore_list)

def load_existing_queries(queries_yaml_path, ignore_list):
    queries_data = []
    if os.path.exists(queries_yaml_path):
        with open(queries_yaml_path, 'r') as file:
            queries_data = yaml.load(file) or []

    # Filter out ignored queries
    filtered_queries_data = []
    for entry in queries_data:
        new_engines = []
        for engine in entry.get('engines', []):
            platform = engine.get('platform', '')
            new_queries = [q for q in engine.get('queries', []) if not should_ignore_query(platform, q, ignore_list)]
            if new_queries:
                new_engines.append({'platform': platform, 'queries': new_queries})
        if new_engines:
            entry['engines'] = new_engines
            filtered_queries_data.append(entry)

    return filtered_queries_data

def generate_queries_yaml(yaml_files, queries_yaml_path, ignore_list):
    queries_data = load_existing_queries(queries_yaml_path, ignore_list)

    # Convert loaded data into a more accessible structure
    data_dict = {f"{item['vendor']}:{item['name']}": item for item in queries_data}

    for yaml_file in yaml_files:
        try:
            with open(yaml_file, 'r') as file:
                template_data = yaml.load(file)
        except Exception as e:
            logger.error(f"Error parsing YAML file {yaml_file}: {e}")
            continue

        if template_data and 'info' in template_data and 'metadata' in template_data['info']:
            metadata = template_data['info']['metadata']
            if 'product' in metadata and 'vendor' in metadata:
                product = metadata['product']
                vendor = metadata['vendor']
                key = f"{vendor}:{product}"
                
                if key not in data_dict:
                    new_entry = {
                        'name': product,
                        'vendor': vendor,
                        'type': 'product',
                        'engines': []
                    }
                    # Initialize new_entry with queries from the current template
                    for field in query_fields:
                        platform = field.replace('-query', '')
                        if field in metadata:
                            queries = metadata[field]
                            if not isinstance(queries, list):
                                queries = [queries]
                            queries = [normalize_query(q) for q in queries]
                            new_entry['engines'].append({
                                'platform': platform,
                                'queries': list(set(queries))
                            })
                    data_dict[key] = new_entry
                    logger.info(f"Added new product/vendor entry: {vendor}:{product}")
                else:
                    # Existing entry, check for new queries
                    entry = data_dict[key]
                    for field in query_fields:
                        platform = field.replace('-query', '')
                        if field in metadata:
                            new_queries = metadata[field]
                            if not isinstance(new_queries, list):
                                new_queries = [new_queries]
                            new_queries = set(normalize_query(q) for q in new_queries)
                            
                            # Find or create engine entry
                            engine_entry = next((e for e in entry['engines'] if e['platform'] == platform), None)
                            if engine_entry is None:
                                engine_entry = {'platform': platform, 'queries': []}
                                entry['engines'].append(engine_entry)
                            
                            existing_queries = set(normalize_query(q) for q in engine_entry['queries'])
                            updated_queries = existing_queries.union(new_queries)
                            
                            if len(updated_queries) > len(existing_queries):
                                added_queries = updated_queries - existing_queries
                                logger.info(f"Added new queries to {vendor}:{product} on platform {platform}: {added_queries}")
                            engine_entry['queries'] = list(updated_queries)
                            
                            # Add mapped queries for other platforms
                            for query in updated_queries:
                                if should_ignore_query(platform, query, ignore_list):
                                    logger.info(f"Skipping ignored query: {platform}:{query}")
                                    continue
                                if platform == "publicwww":
                                    mapped_queries = map_publicwww_query(query)
                                    for other_platform, queries in mapped_queries.items():
                                        for mapped_query in queries:
                                            if should_ignore_query(other_platform, mapped_query, ignore_list):
                                                logger.info(f"Skipping ignored mapped query: {other_platform}:{mapped_query}")
                                                continue
                                        other_engine_entry = next((e for e in entry['engines'] if e['platform'] == other_platform), None)
                                        if other_engine_entry is None:
                                            other_engine_entry = {'platform': other_platform, 'queries': []}
                                            entry['engines'].append(other_engine_entry)
                                        other_engine_entry['queries'].extend(queries)
                                        other_engine_entry['queries'] = list(set(other_engine_entry['queries']))
                                else:
                                    mapped_queries = map_query(query, existing_queries)
                                    if mapped_queries:
                                        for other_platform, queries in mapped_queries.items():
                                            for mapped_query in queries:
                                                if should_ignore_query(other_platform, mapped_query, ignore_list):
                                                    logger.info(f"Skipping ignored mapped query: {other_platform}:{mapped_query}")
                                                    continue
                                            if other_platform != platform:
                                                other_engine_entry = next((e for e in entry['engines'] if e['platform'] == other_platform), None)
                                                if other_engine_entry is None:
                                                    other_engine_entry = {'platform': other_platform, 'queries': []}
                                                    entry['engines'].append(other_engine_entry)
                                                other_engine_entry['queries'].extend(queries)
                                                other_engine_entry['queries'] = list(set(other_engine_entry['queries']))
                                    else:
                                        # Check if the query starts with "http.title:", "http.html:", "intitle:", or "intext:"
                                        if query.startswith("http.title:"):
                                            query_value = query.split(":", 1)[1].strip()
                                            mapped_queries = {
                                                "fofa": [f"title={query_value}"],
                                                "google": [f"intitle:{query_value}"]
                                            }
                                        elif query.startswith("http.html:"):
                                            query_value = query.split(":", 1)[1].strip()
                                            mapped_queries = {
                                                "fofa": [f"body={query_value}"]
                                            }
                                        elif query.startswith("intitle:"):
                                            query_value = query.split(":", 1)[1].strip()
                                            mapped_queries = {
                                                "shodan": [f"http.title:{query_value}"],
                                                "fofa": [f"title={query_value}"]
                                            }
                                        elif query.startswith("intext:"):
                                            query_value = query.split(":", 1)[1].strip()
                                            mapped_queries = {
                                                "shodan": [f"http.html:{query_value}"],
                                                "fofa": [f"body={query_value}"]
                                            }
                                        else:
                                            continue

                                        for other_platform, queries in mapped_queries.items():
                                            for mapped_query in queries:
                                                if should_ignore_query(other_platform, mapped_query, ignore_list):
                                                    logger.info(f"Skipping ignored mapped query: {other_platform}:{mapped_query}")
                                                    continue
                                            other_engine_entry = next((e for e in entry['engines'] if e['platform'] == other_platform), None)
                                            if other_engine_entry is None:
                                                other_engine_entry = {'platform': other_platform, 'queries': []}
                                                entry['engines'].append(other_engine_entry)
                                            other_engine_entry['queries'].extend(queries)
                                            other_engine_entry['queries'] = list(set(other_engine_entry['queries']))

    # Convert the modified data_dict back to list form
    updated_queries_data = list(data_dict.values())
    
    # Perform duplicate checks and filtering for all platforms
    for entry in updated_queries_data:
        for engine in entry['engines']:
            unique_queries = set()
            filtered_queries = []
            quote_preference = {'"': 2, "'": 1, "": 0}  # Preference order: double quotes > single quotes > no quotes
            for query in engine['queries']:
                # Remove quotes and add the query if not already present
                unquoted_query = query.replace('"', '').replace("'", '')
                if unquoted_query not in unique_queries:
                    unique_queries.add(unquoted_query)
                    filtered_queries.append(query)
                else:
                    # If a duplicate is found, compare the quote preference and keep the one with higher preference
                    existing_query = next(q for q in filtered_queries if q.replace('"', '').replace("'", '') == unquoted_query)
                    if quote_preference.get(query[-1], 0) > quote_preference.get(existing_query[-1], 0):
                        filtered_queries.remove(existing_query)
                        filtered_queries.append(query)
            engine['queries'] = filtered_queries
    
    # Perform additional duplicate checks and filtering for the Shodan platform
    for entry in updated_queries_data:
        for engine in entry['engines']:
            if engine['platform'] == 'shodan':
                unique_queries = set()
                filtered_queries = []
                for query in engine['queries']:
                    # Check if the query starts with "http.html:" or "html:"
                    if query.startswith("http.html:") or query.startswith("html:"):
                        # Extract the query content
                        query_content = query.split(":", 1)[1].strip()
                        # Use "http.html:" as the standard format
                        standardized_query = f"http.html:{query_content}"
                        if standardized_query not in unique_queries:
                            unique_queries.add(standardized_query)
                            filtered_queries.append(standardized_query)
                    # Check if the query starts with "http.title:" or "title:"
                    elif query.startswith("http.title:") or query.startswith("title:"):
                        # Extract the query content
                        query_content = query.split(":", 1)[1].strip()
                        # Use "http.title:" as the standard format
                        standardized_query = f"http.title:{query_content}"
                        if standardized_query not in unique_queries:
                            unique_queries.add(standardized_query)
                            filtered_queries.append(standardized_query)
                    else:
                        # For other types of queries, add them as-is if not already present
                        if query not in unique_queries:
                            unique_queries.add(query)
                            filtered_queries.append(query)
                engine['queries'] = filtered_queries
    
    with open(queries_yaml_path, 'w') as file:
        first_entry = True
        for entry in updated_queries_data:
            if not first_entry:
                file.write('\n')  # Add an empty newline before each entry, except the first
            # Dump to string and adjust the indentation
            stream = io.StringIO()
            yaml.dump([entry], stream)
            yaml_str = stream.getvalue()
            yaml_str = '\n'.join(line[2:] if line.startswith('  ') else line for line in yaml_str.splitlines())
            file.write(yaml_str+'\n')
            first_entry = False
    logger.info(f"Updated {queries_yaml_path}")


def main():
    parser = argparse.ArgumentParser(description='Process YAML templates and generate/update QUERIES.yaml')
    parser.add_argument('-tdir', default='nuclei-templates/', help='Directory containing YAML templates')
    parser.add_argument('-t', help='Single template file to process')

    args = parser.parse_args()

    queries_yaml_path = 'awesome-search-queries/QUERIES.yaml'
    ignore_file = 'awesome-search-queries/.ignore'

    if args.t:
        yaml_files = [args.t]
    else:
        yaml_files = read_yaml_files(os.path.expanduser(args.tdir))

    ignore_list = load_ignore_list(ignore_file)
    generate_queries_yaml(yaml_files, queries_yaml_path, ignore_list)

if __name__ == '__main__':
    main()
